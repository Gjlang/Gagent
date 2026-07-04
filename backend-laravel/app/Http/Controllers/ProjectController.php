<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::withCount('testRuns')
            ->latest()
            ->paginate(10);

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'target_type' => ['required', 'in:dummy_website,web_application,android_application'],
            'target_url' => ['nullable', 'url', 'max:255'],
            'status' => ['required', 'string', 'max:50'],
        ]);

        $project = Project::create($validated);

        return redirect()
            ->route('projects.show', $project)
            ->with('success', 'Project created successfully.');
    }

    public function show(Project $project)
    {
        $project->load([
            'testRuns.uxMetric',
            'testRuns.finalFrictionResult',
            'testRuns.mainGAgentResult',
            'testRuns.baselineResult',
            'testRuns.report',
        ]);

        return view('projects.show', compact('project'));
    }
}
