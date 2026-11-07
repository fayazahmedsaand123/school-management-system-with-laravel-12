<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\AcademicYear;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StudentController extends Controller {
    public function index(Request $request) {
        $query = Student::with('user', 'class', 'section');
        if ($request->filled('class_id')) {
            $query->where('class_id', $request->class_id);
        }
        if ($request->filled('section_id')) {
            $query->where('section_id', $request->section_id);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        // CORRECT:
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereHas('user', fn($u) => $u->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%"))
                    ->orWhere('roll_number', 'like', "%$search%")
                    ->orWhere('admission_number', 'like', "%$search%");
            });
        }
        $students     = $query->latest()->paginate(15);
        $classes      = SchoolClass::all();
        $sections     = Section::all();
        return view('students.index', compact('students', 'classes', 'sections'));
    }

    public function create() {
        $classes      = SchoolClass::all();
        $sections     = Section::all();
        $academicYears = AcademicYear::all();
        return view('students.create', compact('classes', 'sections', 'academicYears'));
    }

    public function store(Request $request) {
        $request->validate([
            'name'             => 'required|string|max:255',
            'email'            => 'required|email|unique:users,email',
            'phone'            => 'nullable|string|max:20',
            'date_of_birth'    => 'required|date',
            'gender'           => 'required|in:male,female,other',
            'address'          => 'nullable|string',
            'class_id'         => 'required|exists:classes,id',
            'section_id'       => 'required|exists:sections,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'admission_date'   => 'required|date',
            'photo'            => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        // Handle photo upload
        $photoName = null;
        if ($request->hasFile('photo')) {
            $photoName = time() . '.' . $request->photo->extension();
            $request->photo->storeAs('photos', $photoName, 'public');
        }
        $password = Str::random(10);
        // Create user
        $user = User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($password),
            'phone'         => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender'        => $request->gender,
            'address'       => $request->address,
            'photo'         => $photoName,
            'status'        => 'active',
        ]);
        $user->assignRole('student');
        // Generate admission & roll number
        $admissionNumber = 'ADM-' . date('Y') . '-' . str_pad(Student::max('id') + 1, 4, '0', STR_PAD_LEFT);
        $rollNumber = $request->class_id . $request->section_id . str_pad(Student::max('id') + 1, 3, '0', STR_PAD_LEFT);
        Student::create([
            'user_id'          => $user->id,
            'roll_number'      => $rollNumber,
            'admission_number' => $admissionNumber,
            'admission_date'   => $request->admission_date,
            'class_id'         => $request->class_id,
            'section_id'       => $request->section_id,
            'academic_year_id' => $request->academic_year_id,
            'previous_school'  => $request->previous_school,
            'medical_conditions' => $request->medical_conditions,
            'status'           => 'active',
        ]);
        // Notify admins about new student
        \App\Models\SystemNotification::notifyAdmins(
            'New Student Registered',
            $request->name . ' has been registered in the system',
            route('dashboard.students.index'),
            'fas fa-user-graduate',
            '#2563eb'
        );
        return redirect()->route('dashboard.students.index')
            // CORRECT:
            ->with('success', 'Student registered successfully! Password: ' . $password);
    }

    public function show(Student $student) {
        $student->load('user', 'class', 'section', 'academicYear', 'attendances', 'marks.subject', 'fees');
        return view('students.show', compact('student'));
    }

    public function edit(Student $student) {
        $student->load('user');
        $classes       = SchoolClass::all();
        $sections      = Section::all();
        $academicYears = AcademicYear::all();
        return view('students.edit', compact('student', 'classes', 'sections', 'academicYears'));
    }

    public function update(Request $request, Student $student) {
        $request->validate([
            'name'          => 'required|string|max:255',
            'email'         => 'required|email|unique:users,email,' . $student->user_id,
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'required|exists:sections,id',
            'photo'         => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
        ]);
        if ($request->hasFile('photo')) {
            if ($student->user->photo) {
                Storage::disk('public')->delete('photos/' . $student->user->photo);
            }
            $photoName = time() . '.' . $request->photo->extension();
            $request->photo->storeAs('photos', $photoName, 'public');
            $student->user->update(['photo' => $photoName]);
        }
        $student->user->update([
            'name'          => $request->name,
            'email'         => $request->email,
            'phone'         => $request->phone,
            'date_of_birth' => $request->date_of_birth,
            'gender'        => $request->gender,
            'address'       => $request->address,
        ]);
        $student->update([
            'class_id'         => $request->class_id,
            'section_id'       => $request->section_id,
            'academic_year_id' => $request->academic_year_id,
            'previous_school'  => $request->previous_school,
            'medical_conditions' => $request->medical_conditions,
            'status'           => $request->status,
        ]);
        return redirect()->route('dashboard.students.index')->with('success', 'Student updated successfully!');
    }

    public function destroy(Student $student) {
        if ($student->user->photo) {
            Storage::disk('public')->delete('photos/' . $student->user->photo);
        }
        $student->user->delete();
        // Return JSON for Ajax or redirect for normal request
        if (request()->ajax()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('dashboard.students.index')
            ->with('success', 'Student deleted successfully!');
    }

    public function idCard(Student $student) {
        $student->load('user', 'class', 'section');
        return view('students.id-card', compact('student'));
    }

    public function promoteForm(Student $student) {
        $classes  = SchoolClass::all();
        $sections = Section::all();
        return view('students.promote', compact('student', 'classes', 'sections'));
    }

    public function promote(Request $request, Student $student) {
        $request->validate([
            'to_class_id'   => 'required|exists:classes,id',
            'to_section_id' => 'required|exists:sections,id',
        ]);
        StudentPromotion::create([
            'student_id'      => $student->id,
            'from_class_id'   => $student->class_id,
            'to_class_id'     => $request->to_class_id,
            'from_section_id' => $student->section_id,
            'to_section_id'   => $request->to_section_id,
            'academic_year_id'=> $student->academic_year_id,
            'promoted_by'     => auth()->id(),
            'promoted_at'     => now(),
        ]);
        $student->update([
            'class_id'   => $request->to_class_id,
            'section_id' => $request->to_section_id,
        ]);
        return redirect()->route('dashboard.students.index')->with('success', 'Student promoted successfully!');
    }
}