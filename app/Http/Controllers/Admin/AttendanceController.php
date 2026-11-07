<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Student;
use App\Models\SchoolClass;
use App\Models\Section;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AttendanceController extends Controller {

    public function index(Request $request) {
        $date    = $request->get('date', today()->toDateString());
        $classId = $request->get('class_id');
        $query = Attendance::with('student.user', 'class', 'section')
                           ->where('date', $date);
        if ($classId) {
            $query->where('class_id', $classId);
        }
        $attendances = $query->paginate(20);
        $classes     = SchoolClass::all();
        $summary = [
            'present'  => Attendance::where('date', $date)->where('status', 'present')->count(),
            'absent'   => Attendance::where('date', $date)->where('status', 'absent')->count(),
            'late'     => Attendance::where('date', $date)->where('status', 'late')->count(),
            'half_day' => Attendance::where('date', $date)->where('status', 'half_day')->count(),
        ];
        return view('attendance.index', compact('attendances', 'classes', 'date', 'summary'));
    }

    public function markForm(Request $request) {
        $classes  = SchoolClass::all();
        $sections = Section::all();
        $date     = $request->get('date', today()->toDateString());
        $students = collect();
        $classId  = $request->get('class_id');
        $sectionId = $request->get('section_id');
        if ($classId && $sectionId) {
            $students = Student::with('user')
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->where('status', 'active')
                ->get();
            // Load existing attendance for today
            $existing = Attendance::where('date', $date)
                ->where('class_id', $classId)
                ->where('section_id', $sectionId)
                ->pluck('status', 'student_id');
            $students->each(function($student) use ($existing) {
                $student->attendance_status = $existing->get($student->id, 'present');
            });
        }
        return view('attendance.mark', compact('classes', 'sections', 'students', 'date', 'classId', 'sectionId'));
    }

    public function store(Request $request) {
        $request->validate([
            'date'          => 'required|date',
            'class_id'      => 'required|exists:classes,id',
            'section_id'    => 'required|exists:sections,id',
            'attendance'    => 'required|array',
            'attendance.*'  => 'in:present,absent,late,half_day',
        ]);

        // Verify all student IDs belong to this class and section
        $validStudentIds = Student::where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->pluck('id')
            ->toArray();

        foreach (array_keys($request->attendance) as $studentId) {
            if (!in_array($studentId, $validStudentIds)) {
                return back()->withErrors([
                    'attendance' => 'Invalid student ID: ' . $studentId
                ]);
            }
        }

        // Bulk insert/update — much faster!
        $records = [];
        $now = now();

        foreach ($request->attendance as $studentId => $status) {
            $records[] = [
                'student_id' => $studentId,
                'class_id'   => $request->class_id,
                'section_id' => $request->section_id,
                'date'       => $request->date,
                'status'     => $status,
                'marked_by'  => auth()->id(),
                'remarks'    => $request->remarks[$studentId] ?? null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // One single query instead of many!
        Attendance::upsert(
            $records,
            ['student_id', 'date'],
            ['class_id', 'section_id', 'status', 'marked_by', 'remarks', 'updated_at']
        );

        return redirect()->route('dashboard.attendance.index')
            ->with('success', 'Attendance marked successfully for ' . $request->date);
    }
    
    public function report(Request $request) {
        $month   = $request->get('month', date('m'));
        $year    = $request->get('year', date('Y'));
        $classId = $request->get('class_id');
        $query = Student::with(['user', 'attendances' => function($q) use ($month, $year) {
            $q->whereMonth('date', $month)->whereYear('date', $year);
        }]);
        if ($classId) {
            $query->where('class_id', $classId);
        }
        $students = $query->where('status', 'active')->get();
        $classes  = SchoolClass::all();
        $daysInMonth = Carbon::create($year, $month)->daysInMonth;
        return view('attendance.report', compact('students', 'classes', 'month', 'year', 'daysInMonth'));
    }

    public function getStudentsByClass(Request $request) {
        $students = Student::with('user')
            ->where('class_id', $request->class_id)
            ->where('section_id', $request->section_id)
            ->where('status', 'active')
            ->get()
            ->map(fn($s) => [
                'id'   => $s->id,
                'name' => $s->user->name,
            ]);
        return response()->json($students);
    }
}