/* =====================================================================
   PROCEDURE EDITOR — adapted from process-engine.html prototype
   Loads/saves via server (window.ProcedureEditorData).
   No localStorage library, no runtime execution mode, no "form" node type.
   ===================================================================== */
'use strict';

/* ── 1. CONFIG ─────────────────────────────────────────────────────── */
const NODE_W = 190;
const NODE_H = 84;

const NODE_TYPES = {
  start:     { label:'Start',        icon:'▶',  color:'#3ecf8e' },
  end:       { label:'Koniec',       icon:'⏹',  color:'#ef5a6f' },
  task:      { label:'Zadanie',      icon:'☰',  color:'#5b8def' },
  checklist: { label:'Checklista',   icon:'☑',  color:'#3ecf8e' },
  decision:  { label:'Decyzja',      icon:'◆',  color:'#f0a84e' },
  wait:      { label:'Oczekiwanie',  icon:'⏱',  color:'#8b96b3' },
  note:      { label:'Notatka',      icon:'✎',  color:'#6b7280' },
};
const NODE_TYPE_ORDER = ['start','task','checklist','decision','wait','end','note'];

/* ── 2. STATE ──────────────────────────────────────────────────────── */
const state = {
  currentProcess: null,
  selection: null,
  viewport: { panX: 80, panY: 60, scale: 1 },
  undoStack: [],
  redoStack: [],
  dirty: false,
  logs: [],
  bottomTab: 'validation',
  connecting: null,
  dragging: null,
};

/* ── 3. UTILITIES ──────────────────────────────────────────────────── */
function uid(prefix){ return prefix + '_' + Math.random().toString(36).slice(2,8) + Date.now().toString(36).slice(-4); }
function esc(s){ return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function clamp(v,min,max){ return Math.max(min, Math.min(max, v)); }
function deepClone(o){ return JSON.parse(JSON.stringify(o)); }
function fmtTime(ts){ const d = new Date(ts); return d.toLocaleString('pl-PL',{day:'2-digit',month:'2-digit',hour:'2-digit',minute:'2-digit'}); }

function plog(msg, level){
  state.logs.unshift({ msg, level: level||'info', ts: Date.now() });
  if(state.logs.length > 200) state.logs.pop();
  renderLogs();
}

function toast(msg, variant){
  const wrap = document.getElementById('toastWrap');
  const el = document.createElement('div');
  el.className = 'pe-toast' + (variant ? ' '+variant : '');
  el.textContent = msg;
  wrap.appendChild(el);
  setTimeout(()=>el.remove(), 2800);
}

function markDirty(){ state.dirty = true; document.getElementById('dirtyDot').classList.add('show'); }
function clearDirty(){ state.dirty = false; document.getElementById('dirtyDot').classList.remove('show'); }

/* ── 4. MODEL FACTORIES ────────────────────────────────────────────── */
function createNode(type, x, y){
  const def = NODE_TYPES[type];
  const node = {
    id: uid('node'), type, x, y,
    name: def.label, description: '', instructions: '',
    estimatedDuration: null, durationUnit: 'min',
    icon: def.icon, color: def.color, required: false, assigned_user_id: null,
  };
  if(type === 'checklist') node.checklist = [];
  if(type === 'decision') node.decision = { mode:'yesno', options:[{id:uid('opt'),label:'Tak'},{id:uid('opt'),label:'Nie'}] };
  if(type === 'wait') node.wait = { duration: 5, unit: 'min' };
  return node;
}
function createEdge(from, to, label, optionId){
  return { id: uid('edge'), from, to, label: label||'', condition:'', optionId: optionId||null };
}
function editorUsers(){
  return (window.ProcedureEditorData && window.ProcedureEditorData.users) || [];
}
function userNameById(id){
  if(!id) return '';
  const u = editorUsers().find(x => Number(x.id) === Number(id));
  return u ? u.name : '';
}
function renderAssigneeField(n){
  if(['start','end','note'].includes(n.type)) return '';
  const opts = [`<option value="">— nikt —</option>`]
    .concat(editorUsers().map(u => `<option value="${u.id}" ${Number(n.assigned_user_id)===Number(u.id)?'selected':''}>${esc(u.name)}</option>`))
    .join('');
  return `<label class="pe-field"><span class="pe-lbl">Odpowiedzialny</span><select id="propAssignedUser">${opts}</select></label>`;
}

/* ── 5. UNDO / REDO ────────────────────────────────────────────────── */
function snap(){ return JSON.stringify(state.currentProcess); }
function pushUndo(){
  if(!state.currentProcess) return;
  state.undoStack.push(snap());
  if(state.undoStack.length > 60) state.undoStack.shift();
  state.redoStack = [];
  updateUndoRedoButtons();
}
function undo(){
  if(!state.undoStack.length) return;
  state.redoStack.push(snap());
  state.currentProcess = JSON.parse(state.undoStack.pop());
  state.selection = null;
  markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel(); updateUndoRedoButtons();
  plog('Cofnięto zmianę','info');
}
function redo(){
  if(!state.redoStack.length) return;
  state.undoStack.push(snap());
  state.currentProcess = JSON.parse(state.redoStack.pop());
  state.selection = null;
  markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel(); updateUndoRedoButtons();
  plog('Ponowiono zmianę','info');
}
function updateUndoRedoButtons(){
  document.getElementById('btnUndo').disabled = state.undoStack.length === 0;
  document.getElementById('btnRedo').disabled = state.redoStack.length === 0;
}

/* ── 6. VALIDATION ─────────────────────────────────────────────────── */
function validateProcess(p){
  const issues = [];
  if(!p) return issues;
  const starts = p.nodes.filter(n => n.type === 'start');
  const ends   = p.nodes.filter(n => n.type === 'end');
  if(starts.length === 0) issues.push({level:'error', msg:'Brak węzła Start.'});
  if(starts.length > 1)   issues.push({level:'warning', msg:'Więcej niż jeden węzeł Start.'});
  if(ends.length === 0)   issues.push({level:'error', msg:'Brak węzła Koniec.'});

  if(starts.length){
    const visited = new Set(); const queue = [starts[0].id];
    while(queue.length){ const id=queue.shift(); if(visited.has(id)) continue; visited.add(id); p.edges.filter(e=>e.from===id).forEach(e=>queue.push(e.to)); }
    p.nodes.forEach(n => { if(!visited.has(n.id) && n.type!=='note') issues.push({level:'warning', msg:`Węzeł "${n.name}" nie jest osiągalny ze Startu.`}); });
  }

  p.nodes.forEach(n => {
    if(!n.name||!n.name.trim()) issues.push({level:'error', msg:`Węzeł typu "${NODE_TYPES[n.type]?.label}" nie ma nazwy.`});
    if(n.type==='decision'){
      const opts=(n.decision&&n.decision.options)||[];
      if(opts.length<2) issues.push({level:'error', msg:`Decyzja "${n.name}" ma mniej niż 2 opcje.`});
      opts.forEach(o=>{ if(!p.edges.some(e=>e.from===n.id&&e.optionId===o.id)) issues.push({level:'warning', msg:`Opcja "${o.label}" w węźle "${n.name}" nie ma połączenia.`}); });
    }
    if(n.type==='checklist'&&(!n.checklist||n.checklist.length===0)) issues.push({level:'warning', msg:`Checklista "${n.name}" jest pusta.`});
    if(n.type!=='end'&&n.type!=='note'&&!p.edges.some(e=>e.from===n.id)) issues.push({level:'warning', msg:`Węzeł "${n.name}" nie ma wyjścia.`});
    if(n.type!=='start'&&n.type!=='note'&&!p.edges.some(e=>e.to===n.id)) issues.push({level:'warning', msg:`Węzeł "${n.name}" nie ma wejścia.`});
  });
  p.edges.forEach(e=>{ if(!p.nodes.find(n=>n.id===e.from)||!p.nodes.find(n=>n.id===e.to)) issues.push({level:'error', msg:'Wiszące połączenie do nieistniejącego węzła.'}); });
  return issues;
}

/* ── 7. SERVER SAVE ────────────────────────────────────────────────── */
async function saveToServer(){
  const cfg = window.ProcedureEditorData;
  if(!cfg || !state.currentProcess) return;

  const btnSave = document.getElementById('btnSave');
  btnSave.disabled = true;
  btnSave.innerHTML = '<i class="bi bi-hourglass-split"></i> Zapisywanie…';

  try{
    const body = {
      _method: 'PUT',
      name:        state.currentProcess.name,
      category:    state.currentProcess.category || null,
      subject_type: state.currentProcess.subject_type || null,
      description: state.currentProcess.description || null,
      tags:        state.currentProcess.tags || [],
      definition:  { nodes: state.currentProcess.nodes, edges: state.currentProcess.edges },
    };

    const res = await fetch(cfg.saveUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN':  cfg.csrfToken,
        'Accept':        'application/json',
        'X-HTTP-Method-Override': 'PUT',
      },
      body: JSON.stringify(body),
    });

    if(!res.ok){ const txt = await res.text(); throw new Error(txt); }
    clearDirty();
    toast('✓ Zapisano', 'success');
    plog('Zapisano procedurę na serwerze.', 'success');
  } catch(e){
    toast('✕ Błąd zapisu: ' + e.message, 'danger');
    plog('Błąd zapisu: ' + e.message, 'error');
  } finally {
    btnSave.disabled = false;
    btnSave.innerHTML = '<i class="bi bi-save"></i> Zapisz';
  }
}

/* ── 7b. ASKCHRONO — propozycja przepływu jako niezapisany szkic ───── */
function applyProposal(definition, note){
  if(!definition || !Array.isArray(definition.nodes) || !definition.nodes.length) return false;

  pushUndo();
  state.currentProcess.nodes = definition.nodes;
  state.currentProcess.edges = Array.isArray(definition.edges) ? definition.edges : [];
  state.selection = null;
  markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel(); updateUndoRedoButtons();
  plog(note, 'success');
  toast(note, 'success');
  return true;
}

function proposalIsUnsavedDraft(){
  // Świeżo utworzony szablon ma tylko domyślny Start → Koniec.
  return state.currentProcess.nodes.every(n => n.type === 'start' || n.type === 'end');
}

async function requestChronoFlow(){
  const cfg = window.ProcedureEditorData;
  if(!cfg || !cfg.chronoUrl) return;

  if(!proposalIsUnsavedDraft() && !confirm('Chrono zastąpi bieżący przepływ nową propozycją. Kontynuować? (możesz cofnąć przez Ctrl+Z)')) return;

  const btn = document.getElementById('btnChrono');
  const original = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Myślę…';

  try{
    const res = await fetch(cfg.chronoUrl, {
      method: 'POST',
      headers: { 'Content-Type':'application/json', 'X-CSRF-TOKEN': cfg.csrfToken, 'Accept':'application/json' },
    });
    const data = await res.json().catch(() => ({}));

    if(!res.ok) throw new Error(data.message || 'Model nie zwrócił propozycji.');

    applyProposal(data.definition, `Chrono zaproponował ${data.steps} kroków — sprawdź i zapisz.`);
  } catch(e){
    toast('✕ ' + e.message, 'danger');
    plog('AskChrono: ' + e.message, 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = original;
  }
}

/* ── 8. RENDER: TOOLBAR ────────────────────────────────────────────── */
function renderToolbar(){
  const input = document.getElementById('procNameInput');
  if(state.currentProcess) input.value = state.currentProcess.name;
  document.getElementById('zoomIndicator').textContent = Math.round(state.viewport.scale * 100) + '%';
  document.getElementById('dirtyDot').classList.toggle('show', state.dirty);
}

/* ── 9. RENDER: CANVAS ─────────────────────────────────────────────── */
let canvasEdgeIndex = {};

function renderPalette(){
  const pal = document.getElementById('palette');
  pal.innerHTML = '<div class="pe-p-label">Przeciągnij węzeł</div>' + NODE_TYPE_ORDER.map(type => {
    const d = NODE_TYPES[type];
    return `<div class="pe-palette-item" draggable="true" data-type="${type}">
      <span class="pe-pi-icon" style="background:${d.color}">${d.icon}</span>${d.label}
    </div>`;
  }).join('');
  pal.querySelectorAll('.pe-palette-item').forEach(el => {
    el.addEventListener('dragstart', e => { e.dataTransfer.setData('text/pe-node-type', el.dataset.type); e.dataTransfer.effectAllowed='copy'; });
    el.addEventListener('click', () => {
      if(!state.currentProcess) return;
      const rect = document.getElementById('canvasBg').getBoundingClientRect();
      addNodeToCanvas(el.dataset.type, (rect.width/2 - state.viewport.panX)/state.viewport.scale - NODE_W/2, (rect.height/2 - state.viewport.panY)/state.viewport.scale - NODE_H/2);
    });
  });
}

function nodeMetaLine(n){
  const parts=[];
  if(n.type==='checklist') parts.push(`<span class="pe-chip">${(n.checklist||[]).length} poz.</span>`);
  if(n.type==='decision')  parts.push(`<span class="pe-chip">${(n.decision?.options||[]).length} opcji</span>`);
  if(n.type==='wait')      parts.push(`<span class="pe-chip">${n.wait?.duration||0} ${n.wait?.unit||'min'}</span>`);
  if(n.estimatedDuration)  parts.push(`<span class="pe-chip">⏱ ${n.estimatedDuration} ${n.durationUnit||'min'}</span>`);
  if(n.assigned_user_id){
    const assigneeName = userNameById(n.assigned_user_id);
    if(assigneeName) parts.push(`<span class="pe-chip">${esc(assigneeName)}</span>`);
  }
  if(n.required)           parts.push(`<span class="pe-chip">wymagane</span>`);
  return parts.join('');
}

function renderCanvas(){
  renderPalette();
  const nodesLayer = document.getElementById('nodesLayer');
  const svg = document.getElementById('edgesSvg');
  if(!state.currentProcess){ nodesLayer.innerHTML=''; svg.innerHTML=''; return; }
  const p = state.currentProcess;

  nodesLayer.innerHTML='';
  p.nodes.forEach(n => {
    const def = NODE_TYPES[n.type] || NODE_TYPES.task;
    const div = document.createElement('div');
    div.className = 'pe-node' + (state.selection?.type==='node'&&state.selection.id===n.id?' selected':'');
    div.style.left = n.x+'px'; div.style.top = n.y+'px';
    div.dataset.id = n.id;
    div.innerHTML = `
      <div class="pe-n-del" data-delnode="${n.id}">✕</div>
      ${n.type!=='start'?`<div class="pe-port pe-port-in"></div>`:''}
      <div class="pe-n-top">
        <span class="pe-n-icon" style="background:${n.color}">${n.icon}</span>
        <span class="pe-n-name">${esc(n.name)}</span>
      </div>
      <div class="pe-n-type">${def.label}</div>
      <div class="pe-n-meta">${nodeMetaLine(n)}</div>
      ${n.type!=='end'?`<div class="pe-port pe-port-out" data-portout="${n.id}"></div>`:''}
    `;
    nodesLayer.appendChild(div);
    div.addEventListener('mousedown', e => { if(e.target.closest('.pe-n-del')||e.target.closest('.pe-port-out')) return; startNodeDrag(n.id, e); });
    div.addEventListener('dblclick', () => { selectNode(n.id); setTimeout(()=>{ const el=document.getElementById('propNameInput'); if(el){el.focus();el.select();} },30); });
    div.querySelector('.pe-n-del')?.addEventListener('mousedown', e => { e.stopPropagation(); deleteNode(n.id); });
    div.querySelector('.pe-port-out')?.addEventListener('mousedown', e => { e.preventDefault(); e.stopPropagation(); startConnecting(n.id, e); });
  });

  svg.innerHTML='<defs></defs>';
  canvasEdgeIndex={};
  p.edges.forEach(e=>{
    const from=p.nodes.find(n=>n.id===e.from), to=p.nodes.find(n=>n.id===e.to);
    if(!from||!to) return;
    const grp=document.createElementNS('http://www.w3.org/2000/svg','g'); grp.dataset.edgeId=e.id;
    const sel=state.selection?.type==='edge'&&state.selection.id===e.id;
    const hit=document.createElementNS('http://www.w3.org/2000/svg','path'); hit.setAttribute('class','pe-edge-hit');
    const line=document.createElementNS('http://www.w3.org/2000/svg','path'); line.setAttribute('class','pe-edge-line'+(sel?' selected':''));
    const arrow=document.createElementNS('http://www.w3.org/2000/svg','polygon'); arrow.setAttribute('class','pe-edge-arrow'+(sel?' selected':''));
    grp.appendChild(hit); grp.appendChild(line); grp.appendChild(arrow);
    let labelBg=null,labelText=null;
    if(e.label){ labelBg=document.createElementNS('http://www.w3.org/2000/svg','rect'); labelBg.setAttribute('class','pe-edge-label-bg'); labelBg.setAttribute('rx','8'); labelText=document.createElementNS('http://www.w3.org/2000/svg','text'); labelText.setAttribute('class','pe-edge-label-text'); labelText.textContent=e.label; grp.appendChild(labelBg); grp.appendChild(labelText); }
    svg.appendChild(grp);
    hit.addEventListener('mousedown', ev=>{ ev.stopPropagation(); selectEdge(e.id); });
    (canvasEdgeIndex[e.from]=canvasEdgeIndex[e.from]||[]).push({edge:e,hit,line,arrow,labelBg,labelText});
    (canvasEdgeIndex[e.to]=canvasEdgeIndex[e.to]||[]).push({edge:e,hit,line,arrow,labelBg,labelText});
    updateEdgeGeometry(e,from,to,hit,line,arrow,labelBg,labelText);
  });
  applyViewportTransform();
}

function updateEdgeGeometry(e,from,to,hit,line,arrow,labelBg,labelText){
  const x1=from.x+NODE_W/2, y1=from.y+NODE_H, x2=to.x+NODE_W/2, y2=to.y;
  const dy=Math.max(40,Math.abs(y2-y1)/2);
  const d=`M ${x1} ${y1} C ${x1} ${y1+dy}, ${x2} ${y2-dy}, ${x2} ${y2}`;
  hit.setAttribute('d',d); line.setAttribute('d',d);
  const size=6; arrow.setAttribute('points',`${x2-size},${y2-9} ${x2+size},${y2-9} ${x2},${y2-1}`);
  if(labelBg&&labelText){ const mx=(x1+x2)/2,my=(y1+y2)/2; labelText.setAttribute('x',mx); labelText.setAttribute('y',my+3.5); labelText.setAttribute('text-anchor','middle'); const w=Math.max(24,(labelText.textContent||'').length*6+14); labelBg.setAttribute('x',mx-w/2); labelBg.setAttribute('y',my-10); labelBg.setAttribute('width',w); labelBg.setAttribute('height',20); }
}

function updateEdgesForNode(nodeId){
  const list=canvasEdgeIndex[nodeId]; if(!list) return;
  list.forEach(item=>{ const from=state.currentProcess.nodes.find(n=>n.id===item.edge.from), to=state.currentProcess.nodes.find(n=>n.id===item.edge.to); if(from&&to) updateEdgeGeometry(item.edge,from,to,item.hit,item.line,item.arrow,item.labelBg,item.labelText); });
}

function updateNodeCanvasLabel(nodeId){
  const el=document.querySelector(`.pe-node[data-id="${nodeId}"]`); if(!el) return;
  const n=state.currentProcess.nodes.find(x=>x.id===nodeId); if(!n) return;
  const nameEl=el.querySelector('.pe-n-name'); if(nameEl) nameEl.textContent=n.name;
  const iconEl=el.querySelector('.pe-n-icon'); if(iconEl){ iconEl.textContent=n.icon; iconEl.style.background=n.color; }
  const metaEl=el.querySelector('.pe-n-meta'); if(metaEl) metaEl.innerHTML=nodeMetaLine(n);
}

function applyViewportTransform(){
  const world=document.getElementById('world');
  const {panX,panY,scale}=state.viewport;
  world.style.transform=`translate(${panX}px,${panY}px) scale(${scale})`;
  const bg=document.getElementById('canvasBg');
  bg.style.backgroundSize=`${28*scale}px ${28*scale}px`;
  bg.style.backgroundPosition=`${panX}px ${panY}px`;
  document.getElementById('zoomIndicator').textContent=Math.round(scale*100)+'%';
}

/* ── 10. CANVAS INTERACTIONS ───────────────────────────────────────── */
function addNodeToCanvas(type,x,y){
  if(!state.currentProcess) return;
  pushUndo();
  const node=createNode(type,Math.round(x),Math.round(y));
  state.currentProcess.nodes.push(node);
  markDirty(); state.selection={type:'node',id:node.id};
  renderCanvas(); renderProperties(); renderBottomPanel();
  plog(`Dodano węzeł "${node.name}" (${NODE_TYPES[type].label})`,'success');
}

function startNodeDrag(nodeId,e){
  const n=state.currentProcess.nodes.find(x=>x.id===nodeId); if(!n) return;
  selectNode(nodeId);
  let moved=false;
  state.dragging={id:nodeId,startClientX:e.clientX,startClientY:e.clientY,origX:n.x,origY:n.y};
  const el=document.querySelector(`.pe-node[data-id="${nodeId}"]`);
  if(el) el.style.cursor='grabbing';
  function onMove(ev){ if(!moved){pushUndo();moved=true;} const dx=(ev.clientX-state.dragging.startClientX)/state.viewport.scale, dy=(ev.clientY-state.dragging.startClientY)/state.viewport.scale; n.x=Math.round(state.dragging.origX+dx); n.y=Math.round(state.dragging.origY+dy); if(el){el.style.left=n.x+'px';el.style.top=n.y+'px';} updateEdgesForNode(nodeId); }
  function onUp(){ window.removeEventListener('mousemove',onMove); window.removeEventListener('mouseup',onUp); state.dragging=null; if(el) el.style.cursor='grab'; if(moved){markDirty();renderBottomPanel();} }
  window.addEventListener('mousemove',onMove); window.addEventListener('mouseup',onUp);
}

function startConnecting(fromId,e){
  const svg=document.getElementById('edgesSvg');
  const temp=document.createElementNS('http://www.w3.org/2000/svg','path'); temp.setAttribute('class','pe-temp-line'); svg.appendChild(temp);
  state.connecting={fromId,temp};
  const fromNode=state.currentProcess.nodes.find(n=>n.id===fromId);
  const portEl=document.querySelector(`.pe-node[data-id="${fromId}"] .pe-port-out`);
  portEl?.classList.add('connecting');
  document.body.classList.add('pe-connecting');
  const x1=fromNode.x+NODE_W/2, y1=fromNode.y+NODE_H;
  let hoveredNodeEl=null;
  function setHovered(el){
    if(hoveredNodeEl===el) return;
    hoveredNodeEl?.classList.remove('pe-drop-target');
    hoveredNodeEl=el;
    hoveredNodeEl?.classList.add('pe-drop-target');
  }
  function toWorld(cx,cy){ const rect=document.getElementById('canvasBg').getBoundingClientRect(); return{x:(cx-rect.left-state.viewport.panX)/state.viewport.scale,y:(cy-rect.top-state.viewport.panY)/state.viewport.scale}; }
  function onMove(ev){
    const w=toWorld(ev.clientX,ev.clientY); const dy=Math.max(40,Math.abs(w.y-y1)/2);
    temp.setAttribute('d',`M ${x1} ${y1} C ${x1} ${y1+dy}, ${w.x} ${w.y-dy}, ${w.x} ${w.y}`);
    const targetEl=document.elementFromPoint(ev.clientX,ev.clientY);
    const nodeEl=targetEl?.closest('.pe-node');
    setHovered(nodeEl && nodeEl.dataset.id!==fromId ? nodeEl : null);
  }
  function onUp(ev){
    window.removeEventListener('mousemove',onMove); window.removeEventListener('mouseup',onUp);
    temp.remove(); portEl?.classList.remove('connecting'); document.body.classList.remove('pe-connecting');
    setHovered(null);
    const targetEl=document.elementFromPoint(ev.clientX,ev.clientY); const nodeEl=targetEl?.closest('.pe-node');
    if(nodeEl&&nodeEl.dataset.id&&nodeEl.dataset.id!==fromId) finalizeConnection(fromId,nodeEl.dataset.id);
    state.connecting=null;
  }
  window.addEventListener('mousemove',onMove); window.addEventListener('mouseup',onUp);
}

function finalizeConnection(fromId,toId){
  const p=state.currentProcess;
  const fromNode=p.nodes.find(n=>n.id===fromId);
  if(fromNode.type==='end'){ toast('Węzeł Koniec nie może mieć wychodzących połączeń.'); return; }
  pushUndo(); let label='',optionId=null;
  if(fromNode.type==='decision'){ const opts=fromNode.decision.options; const usedOptIds=p.edges.filter(e=>e.from===fromId).map(e=>e.optionId); const freeOpt=opts.find(o=>!usedOptIds.includes(o.id))||opts[0]; optionId=freeOpt.id; label=freeOpt.label; }
  const edge=createEdge(fromId,toId,label,optionId); p.edges.push(edge); markDirty();
  state.selection={type:'edge',id:edge.id};
  renderCanvas(); renderProperties(); renderBottomPanel();
  plog(`Połączono "${fromNode.name}" → "${p.nodes.find(n=>n.id===toId)?.name}"`,'success');
}

function selectNode(id){ state.selection={type:'node',id}; renderCanvas(); renderProperties(); }
function selectEdge(id){ state.selection={type:'edge',id}; renderCanvas(); renderProperties(); }
function clearSelection(){ state.selection=null; renderCanvas(); renderProperties(); }

function deleteNode(id){
  const p=state.currentProcess; const n=p.nodes.find(x=>x.id===id); if(!n) return;
  if(!confirm(`Usunąć węzeł "${n.name}"? Powiązane połączenia też zostaną usunięte.`)) return;
  pushUndo(); p.nodes=p.nodes.filter(x=>x.id!==id); p.edges=p.edges.filter(e=>e.from!==id&&e.to!==id);
  if(state.selection?.id===id) state.selection=null; markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel(); plog(`Usunięto węzeł "${n.name}"`,'warn');
}
function deleteEdge(id){
  const p=state.currentProcess; pushUndo();
  p.edges=p.edges.filter(e=>e.id!==id);
  if(state.selection?.id===id) state.selection=null; markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel();
}

function duplicateSelectedNode(){
  if(!state.selection||state.selection.type!=='node') return;
  const n=state.currentProcess.nodes.find(x=>x.id===state.selection.id); if(!n) return;
  pushUndo(); const copy=deepClone(n); copy.id=uid('node'); copy.x+=32; copy.y+=32;
  if(copy.type==='decision') copy.decision.options=copy.decision.options.map(o=>({...o,id:uid('opt')}));
  if(copy.type==='checklist') copy.checklist=copy.checklist.map(c=>({...c,id:uid('ci')}));
  state.currentProcess.nodes.push(copy); state.selection={type:'node',id:copy.id}; markDirty();
  renderCanvas(); renderProperties(); renderBottomPanel();
}

/* ── 11. PAN & ZOOM ────────────────────────────────────────────────── */
function initCanvasPanZoom(){
  const bg=document.getElementById('canvasBg');
  bg.addEventListener('mousedown',e=>{
    if(e.target!==bg&&e.target.id!=='world') return;
    clearSelection(); bg.classList.add('panning');
    const start={x:e.clientX,y:e.clientY,panX:state.viewport.panX,panY:state.viewport.panY};
    function onMove(ev){ state.viewport.panX=start.panX+(ev.clientX-start.x); state.viewport.panY=start.panY+(ev.clientY-start.y); applyViewportTransform(); }
    function onUp(){ window.removeEventListener('mousemove',onMove); window.removeEventListener('mouseup',onUp); bg.classList.remove('panning'); }
    window.addEventListener('mousemove',onMove); window.addEventListener('mouseup',onUp);
  });
  bg.addEventListener('wheel',e=>{
    e.preventDefault();
    const rect=bg.getBoundingClientRect(); const mx=e.clientX-rect.left, my=e.clientY-rect.top;
    const worldX=(mx-state.viewport.panX)/state.viewport.scale, worldY=(my-state.viewport.panY)/state.viewport.scale;
    const factor=e.deltaY<0?1.1:1/1.1; const newScale=clamp(state.viewport.scale*factor,0.3,2.2);
    state.viewport.panX=mx-worldX*newScale; state.viewport.panY=my-worldY*newScale; state.viewport.scale=newScale; applyViewportTransform();
  },{passive:false});
  bg.addEventListener('dragover',e=>e.preventDefault());
  bg.addEventListener('drop',e=>{ e.preventDefault(); const type=e.dataTransfer.getData('text/pe-node-type'); if(!type) return; const rect=bg.getBoundingClientRect(); addNodeToCanvas(type,(e.clientX-rect.left-state.viewport.panX)/state.viewport.scale-NODE_W/2,(e.clientY-rect.top-state.viewport.panY)/state.viewport.scale-NODE_H/2); });
  document.getElementById('btnZoomIn').addEventListener('click',()=>zoomBy(1.2));
  document.getElementById('btnZoomOut').addEventListener('click',()=>zoomBy(1/1.2));
  document.getElementById('btnZoomReset').addEventListener('click',()=>{ state.viewport={panX:80,panY:60,scale:1}; applyViewportTransform(); });
}
function zoomBy(f){ const rect=document.getElementById('canvasBg').getBoundingClientRect(); const mx=rect.width/2,my=rect.height/2; const worldX=(mx-state.viewport.panX)/state.viewport.scale, worldY=(my-state.viewport.panY)/state.viewport.scale; const ns=clamp(state.viewport.scale*f,0.3,2.2); state.viewport.panX=mx-worldX*ns; state.viewport.panY=my-worldY*ns; state.viewport.scale=ns; applyViewportTransform(); }

/* ── 12. PROPERTIES PANEL ──────────────────────────────────────────── */
function renderProperties(){
  const body=document.getElementById('propBody'); const sub=document.getElementById('propSub');
  if(!state.currentProcess){ body.innerHTML='<div class="pe-prop-empty">Brak procesu.</div>'; sub.textContent='—'; return; }
  if(!state.selection){ sub.textContent='Proces'; body.innerHTML=renderProcessMetaPanel(state.currentProcess); wireProcessMetaPanel(); return; }
  if(state.selection.type==='node'){
    const n=state.currentProcess.nodes.find(x=>x.id===state.selection.id); if(!n){ state.selection=null; return renderProperties(); }
    sub.textContent=NODE_TYPES[n.type]?.label||n.type; body.innerHTML=renderNodePanel(n); wireNodePanel(n); return;
  }
  if(state.selection.type==='edge'){
    const e=state.currentProcess.edges.find(x=>x.id===state.selection.id); if(!e){ state.selection=null; return renderProperties(); }
    sub.textContent='Połączenie'; body.innerHTML=renderEdgePanel(e); wireEdgePanel(e); return;
  }
}

function renderProcessMetaPanel(p){
  const types = (window.ProcedureEditorData && window.ProcedureEditorData.subjectTypes) || [];
  const typeOptions = [`<option value="">— bez konkretnej encji —</option>`]
    .concat(types.map(t => `<option value="${esc(t.value)}" ${p.subject_type===t.value?'selected':''}>${esc(t.label)}</option>`))
    .join('');
  return `
    <div class="pe-prop-section-title">Informacje o procedurze</div>
    <label class="pe-field"><span class="pe-lbl">Nazwa</span><input type="text" id="metaName" value="${esc(p.name)}"></label>
    <label class="pe-field"><span class="pe-lbl">Kategoria</span><input type="text" id="metaCategory" value="${esc(p.category||'')}"></label>
    <label class="pe-field"><span class="pe-lbl">Dotyczy</span><select id="metaSubjectType">${typeOptions}</select></label>
    <label class="pe-field"><span class="pe-lbl">Opis</span><textarea id="metaDesc">${esc(p.description||'')}</textarea></label>
    <div class="pe-hint">Kliknij węzeł lub połączenie, aby edytować jego właściwości.</div>
  `;
}
function wireProcessMetaPanel(){
  const p=state.currentProcess;
  document.getElementById('metaName').addEventListener('input',function(){ p.name=this.value; markDirty(); document.getElementById('procNameInput').value=p.name; });
  document.getElementById('metaCategory').addEventListener('input',function(){ p.category=this.value; markDirty(); });
  document.getElementById('metaSubjectType').addEventListener('change',function(){ p.subject_type=this.value||null; markDirty(); });
  document.getElementById('metaDesc').addEventListener('input',function(){ p.description=this.value; markDirty(); });
}

function renderNodePanel(n){
  let html=`
    <div class="pe-prop-section-title">Ogólne</div>
    <label class="pe-field"><span class="pe-lbl">Nazwa</span><input type="text" id="propNameInput" value="${esc(n.name)}"></label>
    <label class="pe-field"><span class="pe-lbl">Opis</span><textarea id="propDesc">${esc(n.description||'')}</textarea></label>
    <label class="pe-field"><span class="pe-lbl">Instrukcje (widoczne przy wykonaniu)</span><textarea id="propInstructions">${esc(n.instructions||'')}</textarea></label>
    <div class="pe-row2">
      <label class="pe-field"><span class="pe-lbl">Szac. czas</span><input type="number" min="0" id="propDuration" value="${n.estimatedDuration??''}"></label>
      <label class="pe-field"><span class="pe-lbl">Jednostka</span><select id="propDurationUnit">${['min','godz','dni'].map(u=>`<option value="${u}" ${n.durationUnit===u?'selected':''}>${u}</option>`).join('')}</select></label>
    </div>
    <div class="pe-row2">
      <label class="pe-field"><span class="pe-lbl">Ikona</span><input type="text" id="propIcon" maxlength="2" value="${esc(n.icon)}"></label>
      <label class="pe-field"><span class="pe-lbl">Kolor</span><input type="color" id="propColor" value="${n.color}"></label>
    </div>
    ${renderAssigneeField(n)}
    <div class="pe-checkbox-row"><input type="checkbox" id="propRequired" ${n.required?'checked':''}> Krok wymagany</div>
  `;
  if(n.type==='checklist') html+=renderChecklistEditor(n);
  if(n.type==='decision')  html+=renderDecisionEditor(n);
  if(n.type==='wait')      html+=renderWaitEditor(n);
  html+=`
    <div class="pe-prop-section-title">Akcje</div>
    <div style="display:flex;gap:8px;">
      <button class="pe-btn" id="btnDuplicateNode" style="flex:1;">⧉ Duplikuj</button>
      <button class="pe-btn pe-btn-danger" id="btnDeleteNodeProp" style="flex:1;">🗑 Usuń</button>
    </div>
  `;
  return html;
}

function renderChecklistEditor(n){
  const items=n.checklist||[];
  return `
    <div class="pe-prop-section-title">Pozycje checklisty</div>
    <div class="pe-list-editor" id="checklistEditor">
      ${items.map((it,i)=>`
        <div class="pe-list-row" data-item="${it.id}">
          <div class="pe-row-head">
            <input type="text" class="ci-title" placeholder="Tytuł" value="${esc(it.title)}" data-field="title">
            <button class="pe-icon-btn pe-btn-danger" data-remove-ci="${it.id}">✕</button>
          </div>
          <input type="text" class="ci-desc" placeholder="Opis (opcjonalnie)" value="${esc(it.description||'')}" data-field="description">
          <label class="pe-mini-check"><input type="checkbox" data-field="optional" ${it.optional?'checked':''}> Opcjonalna · poz. ${i+1}</label>
        </div>
      `).join('')}
    </div>
    <button class="pe-add-row-btn" id="btnAddChecklistItem">＋ Dodaj pozycję</button>
  `;
}

function renderDecisionEditor(n){
  const d=n.decision;
  const optsHtml = d.mode==='multi'?`
    <div class="pe-list-editor" id="decisionOptsEditor">
      ${d.options.map(o=>`
        <div class="pe-list-row" data-opt="${o.id}">
          <div class="pe-row-head">
            <input type="text" placeholder="Etykieta opcji" value="${esc(o.label)}" data-field="label">
            <button class="pe-icon-btn pe-btn-danger" data-remove-opt="${o.id}">✕</button>
          </div>
          <input type="text" placeholder="Warunek (wizualnie)" value="${esc(o.condition||'')}" data-field="condition">
        </div>
      `).join('')}
    </div>
    <button class="pe-add-row-btn" id="btnAddDecisionOpt">＋ Dodaj opcję</button>
  `:`<div class="pe-hint">Tryb Tak/Nie — dwie stałe opcje.</div>`;
  return `
    <div class="pe-prop-section-title">Decyzja</div>
    <label class="pe-field"><span class="pe-lbl">Tryb</span>
      <select id="decisionMode"><option value="yesno" ${d.mode==='yesno'?'selected':''}>Tak / Nie</option><option value="multi" ${d.mode==='multi'?'selected':''}>Wiele opcji</option></select>
    </label>
    ${optsHtml}
  `;
}

function renderWaitEditor(n){
  return `
    <div class="pe-prop-section-title">Oczekiwanie</div>
    <div class="pe-row2">
      <label class="pe-field"><span class="pe-lbl">Czas</span><input type="number" min="0" id="waitDuration" value="${n.wait.duration}"></label>
      <label class="pe-field"><span class="pe-lbl">Jednostka</span><select id="waitUnit">${['sek','min','godz','dni'].map(u=>`<option value="${u}" ${n.wait.unit===u?'selected':''}>${u}</option>`).join('')}</select></label>
    </div>
  `;
}

function wireNodePanel(n){
  const $=id=>document.getElementById(id);
  $('propNameInput').addEventListener('input',function(){ n.name=this.value; markDirty(); updateNodeCanvasLabel(n.id); });
  $('propDesc').addEventListener('input',function(){ n.description=this.value; markDirty(); });
  $('propInstructions').addEventListener('input',function(){ n.instructions=this.value; markDirty(); });
  $('propDuration').addEventListener('input',function(){ n.estimatedDuration=this.value===''?null:Number(this.value); markDirty(); updateNodeCanvasLabel(n.id); });
  $('propDurationUnit').addEventListener('change',function(){ n.durationUnit=this.value; markDirty(); updateNodeCanvasLabel(n.id); });
  $('propIcon').addEventListener('input',function(){ n.icon=this.value||NODE_TYPES[n.type].icon; markDirty(); updateNodeCanvasLabel(n.id); });
  $('propColor').addEventListener('input',function(){ n.color=this.value; markDirty(); updateNodeCanvasLabel(n.id); });
  const assigned=$('propAssignedUser');
  if(assigned){
    assigned.addEventListener('change',function(){
      n.assigned_user_id=this.value?Number(this.value):null;
      markDirty();
      updateNodeCanvasLabel(n.id);
    });
  }
  $('propRequired').addEventListener('change',function(){ n.required=this.checked; markDirty(); updateNodeCanvasLabel(n.id); });

  $('btnDuplicateNode').addEventListener('click',duplicateSelectedNode);
  $('btnDeleteNodeProp').addEventListener('click',()=>deleteNode(n.id));

  if(n.type==='checklist') wireChecklistEditor(n);
  if(n.type==='decision')  wireDecisionEditor(n);
  if(n.type==='wait'){
    $('waitDuration').addEventListener('input',function(){ n.wait.duration=Number(this.value)||0; markDirty(); updateNodeCanvasLabel(n.id); });
    $('waitUnit').addEventListener('change',function(){ n.wait.unit=this.value; markDirty(); updateNodeCanvasLabel(n.id); });
  }
}

function wireChecklistEditor(n){
  document.querySelectorAll('#checklistEditor .pe-list-row').forEach(row=>{
    const id=row.dataset.item; const item=n.checklist.find(x=>x.id===id);
    row.querySelectorAll('[data-field]').forEach(inp=>{ const field=inp.dataset.field; const h=()=>{ item[field]=inp.type==='checkbox'?inp.checked:inp.value; markDirty(); updateNodeCanvasLabel(n.id); }; inp.addEventListener(inp.type==='checkbox'?'change':'input',h); });
  });
  document.querySelectorAll('[data-remove-ci]').forEach(btn=>{ btn.addEventListener('click',()=>{ pushUndo(); n.checklist=n.checklist.filter(x=>x.id!==btn.dataset.removeCi); markDirty(); renderProperties(); updateNodeCanvasLabel(n.id); renderBottomPanel(); }); });
  document.getElementById('btnAddChecklistItem').addEventListener('click',()=>{ pushUndo(); n.checklist.push({id:uid('ci'),title:'Nowa pozycja',description:'',optional:false,order:n.checklist.length+1}); markDirty(); renderProperties(); updateNodeCanvasLabel(n.id); renderBottomPanel(); });
}

function wireDecisionEditor(n){
  document.getElementById('decisionMode').addEventListener('change',function(){ pushUndo(); n.decision.mode=this.value; if(this.value==='yesno') n.decision.options=[{id:uid('opt'),label:'Tak'},{id:uid('opt'),label:'Nie'}]; else if(n.decision.options.length<1) n.decision.options=[{id:uid('opt'),label:'Opcja 1'}]; markDirty(); renderProperties(); updateNodeCanvasLabel(n.id); renderBottomPanel(); });
  if(n.decision.mode==='multi'){
    document.querySelectorAll('#decisionOptsEditor .pe-list-row').forEach(row=>{ const id=row.dataset.opt; const opt=n.decision.options.find(x=>x.id===id); row.querySelectorAll('[data-field]').forEach(inp=>{ inp.addEventListener('input',()=>{ opt[inp.dataset.field]=inp.value; markDirty(); updateNodeCanvasLabel(n.id); }); }); });
    document.querySelectorAll('[data-remove-opt]').forEach(btn=>{ btn.addEventListener('click',()=>{ if(n.decision.options.length<=1){toast('Decyzja musi mieć co najmniej 1 opcję.');return;} pushUndo(); const oid=btn.dataset.removeOpt; n.decision.options=n.decision.options.filter(x=>x.id!==oid); state.currentProcess.edges=state.currentProcess.edges.filter(e=>!(e.from===n.id&&e.optionId===oid)); markDirty(); renderProperties(); renderCanvas(); renderBottomPanel(); }); });
    document.getElementById('btnAddDecisionOpt')?.addEventListener('click',()=>{ pushUndo(); n.decision.options.push({id:uid('opt'),label:'Opcja '+(n.decision.options.length+1),condition:''}); markDirty(); renderProperties(); renderBottomPanel(); });
  }
}

function renderEdgePanel(e){
  const p=state.currentProcess; const from=p.nodes.find(n=>n.id===e.from); const to=p.nodes.find(n=>n.id===e.to);
  let optSel='';
  if(from?.type==='decision') optSel=`<label class="pe-field"><span class="pe-lbl">Powiązana opcja decyzji</span><select id="edgeOptionSelect">${from.decision.options.map(o=>`<option value="${o.id}" ${e.optionId===o.id?'selected':''}>${esc(o.label)}</option>`).join('')}</select></label>`;
  return `
    <div class="pe-prop-section-title">Połączenie</div>
    <div class="pe-hint" style="margin-bottom:10px;">${esc(from?.name||'?')} → ${esc(to?.name||'?')}</div>
    ${optSel}
    <label class="pe-field"><span class="pe-lbl">Etykieta</span><input type="text" id="edgeLabel" value="${esc(e.label)}" placeholder="np. Tak, Zatwierdzone…"></label>
    <label class="pe-field"><span class="pe-lbl">Warunek (wizualnie)</span><input type="text" id="edgeCondition" value="${esc(e.condition||'')}" placeholder="np. kwota > 1000"></label>
    <button class="pe-btn pe-btn-danger" id="btnDeleteEdge" style="width:100%;">🗑 Usuń połączenie</button>
  `;
}
function wireEdgePanel(e){
  const optSel=document.getElementById('edgeOptionSelect');
  if(optSel){ optSel.addEventListener('change',function(){ const from=state.currentProcess.nodes.find(n=>n.id===e.from); const opt=from.decision.options.find(o=>o.id===this.value); e.optionId=opt.id; e.label=opt.label; markDirty(); renderCanvas(); renderProperties(); }); }
  document.getElementById('edgeLabel').addEventListener('input',function(){ e.label=this.value; markDirty(); renderCanvas(); });
  document.getElementById('edgeCondition').addEventListener('input',function(){ e.condition=this.value; markDirty(); });
  document.getElementById('btnDeleteEdge').addEventListener('click',()=>deleteEdge(e.id));
}

/* ── 13. BOTTOM PANEL ──────────────────────────────────────────────── */
function renderBottomPanel(){ renderValidation(); renderLogs(); }
function renderValidation(){
  const pane=document.getElementById('paneValidation');
  if(!state.currentProcess){ pane.innerHTML='<div class="pe-prop-empty">Brak procesu.</div>'; return; }
  const issues=validateProcess(state.currentProcess);
  const errCount=issues.filter(i=>i.level==='error').length; const warnCount=issues.filter(i=>i.level==='warning').length;
  const countEl=document.getElementById('validationCount'); countEl.textContent=issues.length; countEl.className='pe-count'+(errCount?' pe-err':warnCount?' pe-warn':'');
  if(!issues.length){ pane.innerHTML='<div class="pe-validation-ok"><i class="bi bi-check-circle-fill"></i> Brak błędów i ostrzeżeń.</div>'; return; }
  pane.innerHTML=issues.map(i=>`<div class="pe-validation-item"><span class="pe-vi-icon">${i.level==='error'?'<i class="bi bi-x-circle-fill" style="color:var(--pe-danger)"></i>':'<i class="bi bi-exclamation-triangle-fill" style="color:var(--pe-warn)"></i>'}</span><span>${esc(i.msg)}</span></div>`).join('');
}
function renderLogs(){
  const pane=document.getElementById('paneLogs'); const countEl=document.getElementById('logsCount'); countEl.textContent=state.logs.length;
  if(!state.logs.length){ pane.innerHTML='<div class="pe-prop-empty">Brak zdarzeń.</div>'; return; }
  pane.innerHTML=state.logs.map(l=>`<div class="pe-log-line"><span class="pe-t">${fmtTime(l.ts)}</span>${esc(l.msg)}</div>`).join('');
}
function switchBottomTab(tab){
  state.bottomTab=tab;
  document.querySelectorAll('.pe-bp-tab').forEach(t=>t.classList.toggle('active',t.dataset.tab===tab));
  document.querySelectorAll('.pe-bp-pane').forEach(p=>p.classList.remove('active'));
  document.getElementById('pane'+tab.charAt(0).toUpperCase()+tab.slice(1))?.classList.add('active');
}

/* ── 14. KEYBOARD SHORTCUTS ────────────────────────────────────────── */
function initKeyboard(){
  document.addEventListener('keydown',e=>{
    const tag=document.activeElement?.tagName;
    if(tag==='INPUT'||tag==='TEXTAREA'||tag==='SELECT') return;
    if((e.ctrlKey||e.metaKey)&&e.key==='z'&&!e.shiftKey){ e.preventDefault(); undo(); return; }
    if((e.ctrlKey||e.metaKey)&&(e.key==='y'||(e.key==='z'&&e.shiftKey))){ e.preventDefault(); redo(); return; }
    if((e.ctrlKey||e.metaKey)&&e.key==='s'){ e.preventDefault(); saveToServer(); return; }
    if((e.key==='Delete'||e.key==='Backspace')&&state.selection){
      if(state.selection.type==='node') deleteNode(state.selection.id);
      else if(state.selection.type==='edge') deleteEdge(state.selection.id);
    }
  });
}

/* ── 15. MAIN INIT ─────────────────────────────────────────────────── */
function initEditor(){
  const cfg=window.ProcedureEditorData;
  if(!cfg) { console.error('ProcedureEditorData not found'); return; }

  const def = cfg.template.definition || { nodes:[], edges:[] };
  state.currentProcess = {
    id:           cfg.template.id,
    name:         cfg.template.name,
    category:     cfg.template.category || '',
    subject_type: cfg.template.subject_type || '',
    description:  cfg.template.description || '',
    tags:         cfg.template.tags || [],
    nodes:        Array.isArray(def.nodes) ? def.nodes : [],
    edges:        Array.isArray(def.edges) ? def.edges : [],
  };

  // Ensure default start/end nodes for blank template
  if(state.currentProcess.nodes.length === 0){
    const startNode = createNode('start', 60, 200);
    const endNode   = createNode('end', 420, 200);
    state.currentProcess.nodes.push(startNode, endNode);
    state.currentProcess.edges.push(createEdge(startNode.id, endNode.id));
    markDirty();
  }

  // Wire toolbar
  document.getElementById('procNameInput').addEventListener('input', function(){
    state.currentProcess.name = this.value; markDirty();
  });
  document.getElementById('btnSave').addEventListener('click', saveToServer);
  document.getElementById('btnChrono')?.addEventListener('click', requestChronoFlow);
  document.getElementById('btnUndo').addEventListener('click', undo);
  document.getElementById('btnRedo').addEventListener('click', redo);
  document.getElementById('btnBack').addEventListener('click', ()=>{
    if(state.dirty && !confirm('Masz niezapisane zmiany. Opuścić edytor?')) return;
    window.location.href = cfg.indexUrl;
  });

  // Bottom panel tabs
  document.querySelectorAll('.pe-bp-tab').forEach(tab=>{
    tab.addEventListener('click',()=>switchBottomTab(tab.dataset.tab));
  });

  initCanvasPanZoom();
  initKeyboard();

  renderToolbar();
  renderCanvas();
  renderProperties();
  renderBottomPanel();
  updateUndoRedoButtons();

  plog('Edytor procedury załadowany.', 'success');
  clearDirty();

  // Propozycja z „Stwórz z Chrono": leży na canvasie, do bazy trafia dopiero po Zapisz.
  if(cfg.chronoProposal){
    applyProposal(cfg.chronoProposal, 'Propozycja Chrono — sprawdź przepływ i kliknij Zapisz.');
  }
}

document.addEventListener('DOMContentLoaded', initEditor);
