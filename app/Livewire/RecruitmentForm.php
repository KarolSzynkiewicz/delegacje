<?php

namespace App\Livewire;

use App\Enums\RecruitmentConsentType;
use App\Enums\RecruitmentStatus;
use App\Models\RecruitmentCandidate;
use App\Models\RecruitmentConsent;
use App\Models\RecruitmentLead;
use App\Models\RecruitmentProcess;
use App\Models\Role;
use App\Support\PhoneNormalizer;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class RecruitmentForm extends Component
{
    use WithFileUploads;

    public string $first_name = '';
    public string $last_name = '';
    public string $email = '';
    public string $phone = '';

    /** @var array<int, int> */
    public array $desired_roles = [];

    public string $city = '';
    public bool $has_driving_license_b = false;
    public bool $speaks_english = false;
    public bool $speaks_french = false;
    public bool $speaks_german = false;
    public ?string $expected_rate_eur = null;
    public string $referral_source = '';
    public $photo = null;

    public bool $consent_rodo = false;
    public bool $consent_recruitment_processing = false;
    public bool $consent_marketing = false;

    public bool $submitted = false;

    protected array $rules = [
        'first_name'   => 'required|string|max:255',
        'last_name'    => 'required|string|max:255',
        'email'        => 'required|email|max:255',
        'phone'        => 'required|digits_between:9,15',
        'city'         => 'nullable|string|max:100',
        'has_driving_license_b' => 'boolean',
        'speaks_english' => 'boolean',
        'speaks_french'  => 'boolean',
        'speaks_german'  => 'boolean',
        'desired_roles'   => 'nullable|array',
        'desired_roles.*' => 'exists:roles,id',
        'expected_rate_eur' => 'nullable|numeric|min:0|max:9999.99',
        'referral_source' => 'nullable|string|in:social_media,employee_referral,recommendation,job_portal',
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
        'phone.required'       => 'Numer telefonu jest wymagany.',
        'phone.digits_between' => 'Podaj prawidłowy numer telefonu.',
        'phone.max'            => 'Numer telefonu może mieć maksymalnie 20 znaków.',
        'expected_rate_eur.numeric' => 'Stawka musi być liczbą.',
        'expected_rate_eur.min'     => 'Stawka nie może być ujemna.',
        'photo.image'          => 'Plik musi być obrazem.',
        'photo.mimes'          => 'Dozwolone formaty: jpeg, png, jpg, webp.',
        'photo.max'            => 'Zdjęcie może mieć maksymalnie 2 MB.',
        'consent_rodo.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych osobowych (RODO).',
        'consent_recruitment_processing.accepted' => 'Musisz wyrazić zgodę na przetwarzanie danych w celu rekrutacji.',
    ];

    public function roles(): Collection
    {
        return Role::orderBy('name')->get();
    }

    public function submit(): void
    {
        $this->phone = PhoneNormalizer::normalize($this->phone) ?? '';
        $this->email = mb_strtolower(trim($this->email));

        $this->validate();

        DB::transaction(function () {
            $candidate = RecruitmentCandidate::where('phone', $this->phone)->first();

            // A repeat application creates only a new lead/process. Candidate master
            // data is deliberately left untouched and can only be edited by a recruiter.
            if (! $candidate) {
                $photoPath = $this->photo
                    ? $this->photo->store('recruitment-photos', 'public')
                    : null;

                $candidate = RecruitmentCandidate::firstOrCreate(
                    ['phone' => $this->phone],
                    [
                        'first_name' => $this->first_name,
                        'last_name' => $this->last_name,
                        'email' => $this->email,
                        'city' => $this->city ?: null,
                        'has_driving_license_b' => $this->has_driving_license_b,
                        'speaks_english' => $this->speaks_english,
                        'speaks_french' => $this->speaks_french,
                        'speaks_german' => $this->speaks_german,
                        'photo_path' => $photoPath,
                        'expected_rate_eur' => $this->expected_rate_eur !== null && $this->expected_rate_eur !== '' ? $this->expected_rate_eur : null,
                    ]
                );

                // Another simultaneous submission may have created the same phone
                // between our lookup and insert. Keep its master data and remove the
                // now-unused upload from this request.
                if (! $candidate->wasRecentlyCreated && $photoPath) {
                    Storage::disk('public')->delete($photoPath);
                }

                if ($candidate->wasRecentlyCreated && ! empty($this->desired_roles)) {
                    $candidate->roles()->attach($this->desired_roles);
                }
            } elseif (! empty($this->desired_roles)) {
                // Returning candidate: merge new roles without removing existing ones.
                $candidate->roles()->syncWithoutDetaching($this->desired_roles);
            }

            $lead = RecruitmentLead::create([
                'candidate_id' => $candidate->id,
                'referral_source' => $this->referral_source ?: null,
            ]);

            $process = RecruitmentProcess::create([
                'lead_id' => $lead->id,
                'candidate_id' => $candidate->id,
                'status' => RecruitmentStatus::Nowy->value,
            ]);

            $now = now();
            $consents = [
                RecruitmentConsentType::Rodo->value => $this->consent_rodo,
                RecruitmentConsentType::RecruitmentProcessing->value => $this->consent_recruitment_processing,
                RecruitmentConsentType::Marketing->value => $this->consent_marketing,
            ];
            foreach ($consents as $type => $given) {
                if ($given) {
                    RecruitmentConsent::create([
                        'candidate_id' => $candidate->id,
                        'recruitment_lead_id' => $lead->id,
                        'type' => $type,
                        'given_at' => $now,
                    ]);
                }
            }
        });

        $this->submitted = true;
    }

    public function render()
    {
        return view('livewire.recruitment-form', [
            'roles' => $this->roles(),
        ]);
    }
}
