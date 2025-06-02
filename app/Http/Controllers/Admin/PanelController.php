<?php

namespace App\Http\Controllers\Admin;

use App\Models\Panel;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use DataTables;

class PanelController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = Panel::with('users')->latest()->get();
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('actions', function ($row) {
                        return '
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-primary editBtn">Edit</button>
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-danger deleteBtn">Delete</button>
                        ';
                    })
                    ->editColumn('user_id', function ($row) {
                        return $row->users->pluck('name')->implode(', ');
                    })
                    ->rawColumns(['actions'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::error('Panel DataTable Error: ' . $e->getMessage());
                return response()->json(['message' => 'Something went wrong while fetching panels.'], 500);
            }
        }

        return view('admin.panels.index');
    }

    public function fetch()
    {
        try {
            $panels = Panel::with('users')->latest()->get();
            return response()->json($panels);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to fetch panels'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'created_by' => 'nullable|string|max:255',
            ]);

            $panel = Panel::create($data);
            return response()->json(['message' => 'Panel created successfully', 'panel' => $panel], 201);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create panel'], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $panel = Panel::findOrFail($id);

            $data = $request->validate([
                'title' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'limit' => 'nullable|integer|min:1',
                'is_active' => 'boolean',
                'created_by' => 'nullable|string|max:255',
            ]);

            $panel->update($data);

            return response()->json(['message' => 'Panel updated successfully', 'panel' => $panel]);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update panel'], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $panel = Panel::findOrFail($id);
            $panel->delete();
            return response()->json(['message' => 'Panel deleted successfully']);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete panel'], 500);
        }
    }

    public function show($id)
    {
        try {
            $panel = Panel::with('users')->findOrFail($id);
            return response()->json($panel);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve panel'], 500);
        }
    }

    public function assignUserToPanel(Request $request, $panelId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $panel = Panel::findOrFail($panelId);

        $panel->users()->syncWithoutDetaching([
            $request->user_id => ['accepted_at' => now()],
        ]);

        return response()->json(['message' => 'User assigned.']);
    }

    public function releaseUserFromPanel(Request $request, $panelId)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $panel = Panel::findOrFail($panelId);

        $panel->users()->updateExistingPivot($request->user_id, ['released_at' => now()]);

        return response()->json(['message' => 'User released.']);
    }
}
