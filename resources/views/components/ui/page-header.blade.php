@php
    $hasSlot = isset($slot) && trim($slot);
    $hasLeft = isset($left);
    $hasRight = isset($right) || $hasSlot;
@endphp

{{--
  Desktop: [lewy] [tytuł] [prawy] w jednym rzędzie.
  Mobile: tytuł na całą szerokość (hierarchia), sloty piętro niżej
  jako cicha belka akcji (małe chipy, nie CTA).
--}}
<div @class([
    'ui-page-header',
    'ui-page-header--has-left' => $hasLeft,
    'ui-page-header--has-right' => $hasRight,
])>
    <div class="ui-page-header__title">
        <h2 class="ui-page-header__heading mb-0">{{ $title }}</h2>
    </div>

    @if($hasLeft || $hasRight)
        <div class="ui-page-header__actions">
            @isset($left)
                <div class="ui-page-header__left">{{ $left }}</div>
            @endisset

            @if($hasRight)
                <div class="ui-page-header__right">
                    @isset($right)
                        {{ $right }}
                    @endisset
                    @if($hasSlot)
                        {{ $slot }}
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
