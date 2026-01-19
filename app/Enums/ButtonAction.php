<?php

namespace App\Enums;

enum ButtonAction: string
{
    case CREATE = 'create';
    case ADD = 'add';
    case EDIT = 'edit';
    case SAVE = 'save';
    case DELETE = 'delete';
    case BACK = 'back';
    case VIEW = 'view';
    case SEARCH = 'search';
    case FILTER = 'filter';
    case EXPORT = 'export';
    case IMPORT = 'import';
    case PRINT = 'print';
    case REFRESH = 'refresh';
    case CANCEL = 'cancel';
    case CONFIRM = 'confirm';

    /**
     * Get the Bootstrap Icons class for this action
     */
    public function icon(): string
    {
        return match($this) {
            self::CREATE, self::ADD => 'bi-plus-circle',
            self::EDIT => 'bi-pencil',
            self::SAVE => 'bi-save',
            self::DELETE => 'bi-trash',
            self::BACK => 'bi-arrow-left',
            self::VIEW => 'bi-eye',
            self::SEARCH => 'bi-search',
            self::FILTER => 'bi-funnel',
            self::EXPORT => 'bi-download',
            self::IMPORT => 'bi-upload',
            self::PRINT => 'bi-printer',
            self::REFRESH => 'bi-arrow-clockwise',
            self::CANCEL => 'bi-x-circle',
            self::CONFIRM => 'bi-check-circle',
        };
    }
}
