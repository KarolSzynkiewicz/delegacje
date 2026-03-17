<?php

namespace App\Livewire;

use Livewire\Component;
use Carbon\Carbon;
use App\Models\Vehicle;
use App\Services\LocationTrackingService;

class DeparturePlannerV2 extends Component
{
    // Podstawowe dane formularza
    public $departureDate;
    public $endDate;
    public $vehicleId;
    public $currentStep = 1;
    
    // Dane z wyborów użytkownika (przechowywane w głównym komponencie)
    public $assignments = []; // [day_1 => [project_id => [role_id => [employee_id => ...]]]]
    public $assignmentRanges = []; // [employee_id_projectId_roleId => ['start_date' => ..., 'end_date' => ..., 'employee_id' => ..., 'project_id' => ..., 'role_id' => ...]]
    public $vehicleSeats = []; // [seat_index => ['employee_id' => ..., 'position' => 'driver'|'passenger']]
    public $accommodationAssignments = []; // [employee_id => ['accommodation_id' => ..., 'start_date' => ..., 'end_date' => ...]]
    public $vehicleAssignments = []; // [employee_id => ['vehicle_id' => ..., 'position' => ..., 'start_date' => ..., 'end_date' => ...]]
    public $routeData = null; // Route planning data
    
    // Listenery na eventy z podkomponentów
    protected $listeners = [
        // Step 1 - Project Assignments
        'assignment-added' => 'handleAssignmentAdded',
        'assignment-removed' => 'handleAssignmentRemoved',
        'assignment-range-added' => 'handleAssignmentRangeAdded',
        'assignment-range-removed' => 'handleAssignmentRangeRemoved',
        'vehicle-seat-updated' => 'handleVehicleSeatUpdated',
        
        // Step 2 - Accommodation Assignments
        'accommodation-assigned' => 'handleAccommodationAssigned',
        'accommodation-removed' => 'handleAccommodationRemoved',
        
        // Step 3 - Vehicle Assignments
        'vehicle-assigned' => 'handleVehicleAssigned',
        'vehicle-assignment-removed' => 'handleVehicleAssignmentRemoved',
        
        // Navigation
        'go-to-step' => 'goToStep',
        'save-departure' => 'saveDeparture',
        
        // Step 4 - Route Planning
        'route-planned' => 'handleRoutePlanned',
    ];

    public function mount($departureDate = null, $endDate = null, $vehicleId = null)
    {
        $this->departureDate = $departureDate ?? date('Y-m-d');
        $this->endDate = $endDate ?? date('Y-m-d');
        $this->vehicleId = $vehicleId;
    }
    
    // Automatyczne odświeżanie po zmianie pojazdu
    public function updatedVehicleId()
    {
        // Wyczyść miejsca w aucie gdy zmienia się pojazd
        $this->vehicleSeats = [];
        
        // Inicjalizuj miejsca dla nowego pojazdu
        if ($this->vehicleId) {
            $vehicle = \App\Models\Vehicle::find($this->vehicleId);
            if ($vehicle && $vehicle->capacity) {
                // Inicjalizuj wszystkie miejsca jako puste
                for ($i = 0; $i < $vehicle->capacity; $i++) {
                    $this->vehicleSeats[$i] = [
                        'employee_id' => null,
                        'position' => 'passenger',
                    ];
                }
                
                // Wczytaj wszystkie istniejące przypisania z assignmentRanges
                $seatIndex = 0;
                foreach ($this->assignmentRanges as $assignmentRange) {
                    $employeeId = $assignmentRange['employee_id'];
                    
                    // Sprawdź czy pracownik już nie jest w aucie (duplikaty)
                    $alreadyInVehicle = false;
                    foreach ($this->vehicleSeats as $seat) {
                        if (!empty($seat['employee_id']) && $seat['employee_id'] == $employeeId) {
                            $alreadyInVehicle = true;
                            break;
                        }
                    }
                    
                    // Jeśli nie ma go w aucie i jest miejsce, przypisz
                    if (!$alreadyInVehicle && $seatIndex < $vehicle->capacity) {
                        $this->vehicleSeats[$seatIndex] = [
                            'employee_id' => $employeeId,
                            'position' => 'passenger', // Domyślnie pasażer
                        ];
                        $seatIndex++;
                    }
                }
            }
        }
        
        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }
    
    // Automatyczne odświeżanie po zmianie miejsc w aucie
    public function updatedVehicleSeats()
    {
        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }
    
    // ============================================
    // Step 1 - Project Assignment Handlers
    // ============================================
    
    public function handleAssignmentAdded($data)
    {
        // $data: ['day' => 'day_1', 'project_id' => 1, 'role_id' => 2, 'employee_id' => 3]
        $day = $data['day'];
        $projectId = $data['project_id'];
        $roleId = $data['role_id'];
        $employeeId = $data['employee_id'];
        
        if (!isset($this->assignments[$day])) {
            $this->assignments[$day] = [];
        }
        if (!isset($this->assignments[$day][$projectId])) {
            $this->assignments[$day][$projectId] = [];
        }
        if (!isset($this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId] = [];
        }
        
        if (!in_array($employeeId, $this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId][] = $employeeId;
        }
    }
    
    public function handleAssignmentRemoved($data = [])
    {
        // Jeśli $data jest puste lub nie jest tablicą, nie rób nic
        if (empty($data) || !is_array($data)) {
            return;
        }
        
        $day = $data['day'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        $employeeId = $data['employee_id'] ?? null;
        
        if (!$day || !$projectId || !$roleId || !$employeeId) {
            return;
        }
        
        if (isset($this->assignments[$day][$projectId][$roleId])) {
            $this->assignments[$day][$projectId][$roleId] = array_values(
                array_filter($this->assignments[$day][$projectId][$roleId], fn($id) => $id != $employeeId)
            );
            
            // Clean up empty arrays
            if (empty($this->assignments[$day][$projectId][$roleId])) {
                unset($this->assignments[$day][$projectId][$roleId]);
            }
            if (empty($this->assignments[$day][$projectId])) {
                unset($this->assignments[$day][$projectId]);
            }
            if (empty($this->assignments[$day])) {
                unset($this->assignments[$day]);
            }
        }
    }
    
    public function handleAssignmentRangeAdded($data)
    {
        // $data: ['employee_id' => 1, 'project_id' => 2, 'role_id' => 3, 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $key = $data['employee_id'] . '_' . $data['project_id'] . '_' . $data['role_id'];
        $this->assignmentRanges[$key] = [
            'employee_id' => $data['employee_id'],
            'project_id' => $data['project_id'],
            'role_id' => $data['role_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
        
        // Automatycznie przypisz pracownika do pierwszego wolnego miejsca w aucie
        if ($this->vehicleId) {
            $employeeId = $data['employee_id'];
            $alreadyInVehicle = false;
            
            // Inicjalizuj vehicleSeats jeśli są puste
            if (empty($this->vehicleSeats)) {
                // Pobierz pojazd i zainicjalizuj miejsca
                $vehicle = \App\Models\Vehicle::find($this->vehicleId);
                if ($vehicle && $vehicle->capacity) {
                    for ($i = 0; $i < $vehicle->capacity; $i++) {
                        $this->vehicleSeats[$i] = [
                            'employee_id' => null,
                            'position' => 'passenger',
                        ];
                    }
                }
            }
            
            // Sprawdź czy pracownik już jest w aucie
            foreach ($this->vehicleSeats as $seat) {
                if (!empty($seat['employee_id']) && $seat['employee_id'] == $employeeId) {
                    $alreadyInVehicle = true;
                    break;
                }
            }
            
            // Jeśli nie ma go w aucie, przypisz do pierwszego wolnego miejsca
            if (!$alreadyInVehicle) {
                foreach ($this->vehicleSeats as $index => $seat) {
                    if (empty($seat['employee_id'])) {
                        $this->vehicleSeats[$index] = [
                            'employee_id' => $employeeId,
                            'position' => 'passenger', // Domyślnie pasażer
                        ];
                        break;
                    }
                }
            }
        }
        
        // Dispatch event to refresh child component
        $this->dispatch('refresh-assignments');
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }
    
    public function handleAssignmentRangeRemoved($data = [])
    {
        // Jeśli $data jest puste lub nie jest tablicą, nie rób nic
        if (empty($data) || !is_array($data)) {
            return;
        }
        
        $employeeId = $data['employee_id'] ?? null;
        $projectId = $data['project_id'] ?? null;
        $roleId = $data['role_id'] ?? null;
        
        if (!$employeeId || !$projectId || !$roleId) {
            return;
        }
        
        $key = $employeeId . '_' . $projectId . '_' . $roleId;
        unset($this->assignmentRanges[$key]);
        
        // Usuń również pracownika z vehicleSeats jeśli tam jest
        if ($this->vehicleId && !empty($this->vehicleSeats)) {
            foreach ($this->vehicleSeats as $index => $seat) {
                if (!empty($seat['employee_id']) && $seat['employee_id'] == $employeeId) {
                    $this->vehicleSeats[$index] = [
                        'employee_id' => null,
                        'position' => 'passenger',
                    ];
                    break;
                }
            }
            // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
            $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
        }
        
        // Dispatch event to refresh child component
        $this->dispatch('refresh-assignments');
    }
    
    public function handleVehicleSeatUpdated($data)
    {
        // $data: ['seat_index' => 0, 'employee_id' => 1, 'position' => 'driver']
        $seatIndex = $data['seat_index'];
        $this->vehicleSeats[$seatIndex] = [
            'employee_id' => $data['employee_id'] ?? null,
            'position' => $data['position'] ?? 'passenger',
        ];
        
        // Dispatch event z aktualnymi vehicleSeats do komponentu Step1
        $this->dispatch('vehicle-seats-updated', vehicleSeats: $this->vehicleSeats);
    }
    
    // ============================================
    // Step 2 - Accommodation Assignment Handlers
    // ============================================
    
    public function handleAccommodationAssigned($data)
    {
        // $data: ['employee_id' => 1, 'accommodation_id' => 2, 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $this->accommodationAssignments[$data['employee_id']] = [
            'accommodation_id' => $data['accommodation_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }
    
    public function handleAccommodationRemoved($data)
    {
        // $data: ['employee_id' => 1]
        unset($this->accommodationAssignments[$data['employee_id']]);
    }
    
    // ============================================
    // Step 3 - Vehicle Assignment Handlers
    // ============================================
    
    public function handleVehicleAssigned($data)
    {
        // $data: ['employee_id' => 1, 'vehicle_id' => 2, 'position' => 'driver', 'start_date' => '2024-01-01', 'end_date' => '2024-01-10']
        $this->vehicleAssignments[$data['employee_id']] = [
            'vehicle_id' => $data['vehicle_id'],
            'position' => $data['position'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
        ];
    }
    
    public function handleVehicleAssignmentRemoved($data)
    {
        // $data: ['employee_id' => 1]
        unset($this->vehicleAssignments[$data['employee_id']]);
    }
    
    // ============================================
    // Navigation
    // ============================================
    
    public function goToStep($step)
    {
        $step = (int)$step;
        
        // Walidacja przed przejściem do następnego kroku
        if ($step === 2) {
            $hasAssignments = !empty($this->assignments) || !empty($this->assignmentRanges);
            if (!$hasAssignments) {
                $this->dispatch('error', message: 'Musisz przypisać przynajmniej jednego pracownika do projektu przed przejściem dalej.');
                return;
            }
        }
        
        $this->currentStep = $step;
    }
    
    public function handleRoutePlanned($data)
    {
        // Store complete route data including waypoint order, distance, and duration
        $this->routeData = [
            'route_distance' => $data['route_distance'] ?? null,
            'route_duration' => $data['route_duration'] ?? null,
            'route_waypoints' => $data['route_waypoints'] ?? [], // Order of accommodation IDs
        ];
    }
    
    public function saveDeparture()
    {
        // Walidacja przed zapisem
        if (empty($this->assignments) && empty($this->assignmentRanges)) {
            $this->dispatch('error', message: 'Musisz przypisać przynajmniej jednego pracownika do projektu.');
            return;
        }

        // Zapisz dane w sesji (route_data może być duże, więc lepiej przez sesję)
        session([
            'departure_v2_data' => [
                'departure_date' => $this->departureDate,
                'end_date' => $this->endDate,
                'vehicle_id' => $this->vehicleId,
                'assignments' => $this->assignments,
                'assignment_ranges' => $this->assignmentRanges,
                'vehicle_seats' => $this->vehicleSeats,
                'accommodation_assignments' => $this->accommodationAssignments,
                'vehicle_assignments' => $this->vehicleAssignments,
                'route_data' => $this->routeData, // Contains: route_distance, route_duration, route_waypoints
            ]
        ]);

        // Przekieruj do kontrolera, który zapisze dane
        return redirect()->route('departures.store-v2');
    }

    /**
     * Get vehicles that are in base on departure date
     */
    public function getAvailableVehiclesProperty()
    {
        if (!$this->departureDate) {
            return collect();
        }
        
        $departureDate = Carbon::parse($this->departureDate);
        $locationTrackingService = app(LocationTrackingService::class);
        
        // Pobierz wszystkie pojazdy firmowe
        $vehicles = Vehicle::where('type', 'company_vehicle')
            ->orderBy('registration_number')
            ->get();
        
        // Filtruj tylko te, które są w bazie na dzień wyjazdu
        $availableVehicles = $vehicles->filter(function ($vehicle) use ($departureDate, $locationTrackingService) {
            $status = $locationTrackingService->getVehicleLocationStatus($vehicle, $departureDate);
            // Pojazd jest dostępny jeśli nie jest w podróży i nie jest poza bazą
            return !$status['in_transit'] && !$status['outside_base'];
        });
        
        return $availableVehicles;
    }

    public function render()
    {
        return view('livewire.departure-planner-v2');
    }
}
