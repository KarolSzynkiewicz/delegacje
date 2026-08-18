<?php

namespace App\Contracts;

/**
 * Encja, do której zadanie może wracać linkiem na liście / karcie zadania.
 * Nowe źródła (ZW, pojazd, …) implementują ten kontrakt zamiast dokładać FK na project_tasks.
 */
interface TaskSubject
{
    public function taskCardUrl(): string;

    public function taskCardLabel(): string;

    public function taskCardIcon(): string;
}
