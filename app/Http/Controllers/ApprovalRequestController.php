<?php

namespace App\Http\Controllers;

use App\Enums\ApprovalDecision;
use App\Models\ApprovalRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalRequestController extends Controller
{
    public function show(ApprovalRequest $approvalRequest): View
    {
        $this->authorize('view', $approvalRequest);

        $approvalRequest->load([
            'approver',
            'createdBy',
            'decidedBy',
            'sprint',
            'attachments.uploader',
            'comment.commentable',
            'procedureRun.template',
            'procedureRun.task',
            'procedureRun.subject',
        ]);

        return view('approval-requests.show', [
            'approval' => $approvalRequest,
        ]);
    }

    public function decide(Request $request, ApprovalRequest $approvalRequest): RedirectResponse
    {
        abort_unless($approvalRequest->isApprover($request->user()), 403);

        if ($approvalRequest->isDecided()) {
            return redirect()
                ->route('approval-requests.show', $approvalRequest)
                ->with('error', 'Decyzja jest już podjęta i nie da się jej zmienić.');
        }

        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'comment' => ['nullable', 'string', 'max:5000'],
        ]);

        $approvalRequest->decide(
            ApprovalDecision::from($validated['decision']),
            $request->user(),
            (string) ($validated['comment'] ?? ''),
        );

        $label = $approvalRequest->fresh()->decision?->label() ?? 'decyzja';

        return redirect()
            ->route('approval-requests.show', $approvalRequest)
            ->with('success', 'Wniosek oznaczony jako '.$label.'.');
    }
}
