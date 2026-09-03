@props(['run'])

<x-procedure-flow
    :definition="$run->definition()"
    :highlight="$run->flowHighlight()"
    :tokens="$run->join_tokens ?? []"
    legend="run"
/>
