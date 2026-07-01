<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CourseController extends Controller
{
    public function index(Request $request) {
        $query = Course::query();

    // Filter Kategori
        if ($request->has('categories') && !in_array('Semua', $request->categories)) {
        $query->whereIn('category', $request->categories);
        }

    // Filter Level
        if ($request->has('levels')) {
        $query->whereIn('level', $request->levels);
        }

    // Ambil data yang sudah difilter
        $courses = $query->latest()->get();

        return view('learningpath.index', compact('courses'));
    }

    public function show($id) {
        $course = Course::findOrFail($id);
        $isPurchased = false;

        if (Auth::check()) {
            $isPurchased = Purchase::where('user_id', Auth::id())
                ->where('course_id', $id)
                ->where('status', 'success')
                ->exists();
        }

        return view('learningpath.show', compact('course', 'isPurchased'));
    }
    
}



