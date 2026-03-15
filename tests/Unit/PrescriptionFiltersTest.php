<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Prescription;
use App\Models\Appointment;
use App\Filters\Doctor\PrescriptionFilters;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

class PrescriptionFiltersTest extends TestCase
{
    use RefreshDatabase;

    protected $doctor;
    protected $patient1;
    protected $patient2;
    protected $otherDoctor;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->doctor = User::factory()->create(['role' => 'doctor']);
        $this->patient1 = User::factory()->create(['role' => 'user', 'first_name' => 'John', 'last_name' => 'Doe']);
        $this->patient2 = User::factory()->create(['role' => 'user', 'first_name' => 'Jane', 'last_name' => 'Smith']);
        $this->otherDoctor = User::factory()->create(['role' => 'doctor']);
    }

    /** @test */
    public function it_filters_prescriptions_by_doctor_id_only()
    {
        // Create prescriptions for different doctors
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->otherDoctor->id,
            'patient_id' => $this->patient2->id
        ]);
        
        $prescription3 = Prescription::factory()->create([
            'doctor_id' => null,
            'patient_id' => $this->patient1->id
        ]);

        // Create filter with doctor_id
        $request = new Request(['doctor_id' => $this->doctor->id]);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescriptions for this doctor, not null doctor_id
        $this->assertCount(1, $results);
        $this->assertEquals($prescription1->id, $results->first()->id);
    }

    /** @test */
    public function it_filters_prescriptions_by_doctor_patients_only()
    {
        // Create appointments between doctor and patient1
        Appointment::factory()->create([
            'user_id' => $this->patient1->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $this->doctor->id
        ]);
        
        // Create prescriptions
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // Doctor has appointment with this patient
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Doctor has NO appointment with this patient
        ]);

        // Create filter with doctor_patients_only
        $request = new Request(['doctor_patients_only' => $this->doctor->id]);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescriptions for patients the doctor has appointments with
        $this->assertCount(1, $results);
        $this->assertEquals($prescription1->id, $results->first()->id);
    }

    /** @test */
    public function it_searches_patients_by_first_name()
    {
        // Create prescriptions
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // John Doe
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Jane Smith
        ]);

        // Search for "john"
        $request = new Request(['search' => 'john']);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescription for John Doe
        $this->assertCount(1, $results);
        $this->assertEquals($prescription1->id, $results->first()->id);
    }

    /** @test */
    public function it_searches_patients_by_last_name()
    {
        // Create prescriptions
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // John Doe
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Jane Smith
        ]);

        // Search for "smith"
        $request = new Request(['search' => 'smith']);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescription for Jane Smith
        $this->assertCount(1, $results);
        $this->assertEquals($prescription2->id, $results->first()->id);
    }

    /** @test */
    public function it_searches_patients_by_partial_name()
    {
        // Create prescriptions
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // John Doe
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Jane Smith
        ]);

        // Search for "ja" (should match Jane)
        $request = new Request(['search' => 'ja']);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescription for Jane Smith
        $this->assertCount(1, $results);
        $this->assertEquals($prescription2->id, $results->first()->id);
    }

    /** @test */
    public function it_returns_no_results_for_non_matching_search()
    {
        // Create prescriptions
        Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // John Doe
        ]);
        
        Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Jane Smith
        ]);

        // Search for non-existing name
        $request = new Request(['search' => 'nonexistent']);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should return no results
        $this->assertCount(0, $results);
    }

    /** @test */
    public function it_combines_doctor_filter_and_patient_relationship_filter()
    {
        // Create appointment between doctor and patient1
        Appointment::factory()->create([
            'user_id' => $this->patient1->id,
            'bookable_type' => 'App\Models\User',
            'bookable_id' => $this->doctor->id
        ]);
        
        // Create prescriptions
        $prescription1 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient1->id // Doctor has appointment with this patient
        ]);
        
        $prescription2 = Prescription::factory()->create([
            'doctor_id' => $this->doctor->id,
            'patient_id' => $this->patient2->id // Doctor has NO appointment with this patient
        ]);
        
        $prescription3 = Prescription::factory()->create([
            'doctor_id' => $this->otherDoctor->id,
            'patient_id' => $this->patient1->id // Different doctor
        ]);

        // Apply both filters
        $request = new Request([
            'doctor_id' => $this->doctor->id,
            'doctor_patients_only' => $this->doctor->id
        ]);
        $filter = new PrescriptionFilters($request);
        
        $query = Prescription::query();
        $filter->apply($query);
        
        $results = $query->get();
        
        // Should only return prescription1 (correct doctor + has appointment with patient)
        $this->assertCount(1, $results);
        $this->assertEquals($prescription1->id, $results->first()->id);
    }
}
