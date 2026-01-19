<?php

namespace App\View\Components\Ui;

use App\Enums\ButtonAction;
use Illuminate\View\Component;
use Illuminate\View\View;

class PageHeader extends Component
{
    public function __construct(
        public string $title,
        // Primary action
        public ?string $primaryActionLabel = null,
        public ?string $primaryActionHref = null,
        public ?ButtonAction $primaryActionAction = null,
        public string $primaryActionVariant = 'primary',
        // Secondary action
        public ?string $secondaryActionLabel = null,
        public ?string $secondaryActionHref = null,
        public ?ButtonAction $secondaryActionAction = null,
        public string $secondaryActionVariant = 'ghost',
    ) {
        // Convert string to enum if provided as string
        if (is_string($primaryActionAction)) {
            $this->primaryActionAction = ButtonAction::tryFrom($primaryActionAction);
        }
        if (is_string($secondaryActionAction)) {
            $this->secondaryActionAction = ButtonAction::tryFrom($secondaryActionAction);
        }
    }

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('components.ui.page-header');
    }
}
