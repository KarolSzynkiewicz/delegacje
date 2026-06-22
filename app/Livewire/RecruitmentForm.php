<?php

namespace App\Livewire;

use App\Models\RecruitmentApplication;
use Livewire\Component;
use Livewire\WithFileUploads;

class RecruitmentForm extends Component
{
    use WithFileUploads;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';
    public string $desired_role = '';
    public string $cover_letter = '';
    public $photo = null;

    public bool $consent_rodo = false;
    public bool $consent_recruitment_processing = false;
    public bool $consent_marketing = false;

    public bool $submitted = false;

    protected array $rules = [
        'first_name'   => 'required|string|max:255',
        'last_name'    => 'required|string|max:255',
        'email'        => 'required|email|max:255|unique:recruitment_applications,email',
        'phone'        => 'nullable|string|max:20',
        'desired_role' => 'nullable|string|max:255',
        'cover_letter' => 'nullable|string|max:5000',
        'photo'        => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        'consent_rodo' => 'accepted',
        'consent_recruitment_processing' => 'accepted',
        'consent_marketing' => 'boolean',
    ];

    protected array $messages = [
        'first_name.required'  => 'Imię jest wymagane.',
        'first_name.max'       => 'Imię może mieć maksymalnie 255 znaków.',
        'last_name.required'   => 'Nazwisko jest wymagane.',
        'last_name.max'        => 'Nazwisko może mieć maksymalnie 255 znaków.',
        'email.required'       => 'Adres e-mail jest wymagany.',
        'email.email'          => 'Podaj prawidłowy adres e-mail.',
        'email.unique'         => 'Zgłoszenie z tym adresem e-mail zostało już przesłane.',
        'phone.max'            => 'Numer telefonu może mieć maksymalnie 20 znaków.',
        'desired_role.max'     => 'Pole stanowiska może mieć maksymalnie 255 znaków.',
        'cover_letter.max'     => 'List motywacyjny może mieć maksymalnie 5000 znaków.',
        'photo.image'          => 'Plik musi być obrazem.',
        'photo.mimes'          => 'Dozwolone formaty: jpeg, png, jpg, webp.',
        'photo.max'            => 'Zdjęcie może mieć maksymalnie 2 MB.',
        'consent_rodo.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych (RODO).',
        'consent_recruitment_processing.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych w celu rekrutacji.',
    ];

    public function submit(): void
    {
        $this->validate();

        $photoPath = null;
        if ($this->photo) {
            $photoPath = $this->photo->store('recruitment-photos', 'public');
        }

        RecruitmentApplication::create([
            'first_name'   => $this->first_name,
            'last_name'    => $this->last_name,
            'email'        => $this->email,
            'phone'        => $this->phone ?: null,
            'desired_role' => $this->desired_role ?: null,
            'cover_letter' => $this->cover_letter ?: null,
            'photo_path'   => $photoPath,
            'consent_rodo' => $this->consent_rodo,
            'consent_recruitment_processing' => $this->consent_recruitment_processing,
            'consent_marketing' => $this->consent_marketing,
            'consent_given_at' => now(),
        ]);

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.recruitment-form');
    }
}
