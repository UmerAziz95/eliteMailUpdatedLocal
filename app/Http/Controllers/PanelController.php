<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use DataTables;
//models
use App\Models\Panel;
use App\Models\Order;
use App\Models\OrderPanel;
use App\Models\OrderPanelSplit; 
use App\Models\UserOrderPanelAssignment; 


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


    public function Contractorindex(Request $request)
    {
        if ($request->ajax()) {
            try {
                $data = Panel::where('is_active',true)->with(['order_panels.orderPanelSplit'])->get(); 
                return DataTables::of($data)
                    ->addIndexColumn()
                    ->addColumn('actions', function ($row) {
                        return '
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-primary editBtn">Edit</button>
                            <button data-id="'.$row->id.'" class="btn btn-sm btn-danger deleteBtn">Delete</button>
                        ';
                    })
                    ->rawColumns(['actions'])
                    ->make(true);
            } catch (\Exception $e) {
                Log::error('Panel DataTable Error: ' . $e->getMessage());
                return response()->json(['message' => 'Something went wrong while fetching panels.'.$e->getMessage()], 500);
            }
        }

        return view('contractor.panels.index');
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


     public function assignPanelToUser(Request $request, $order_panel_id)
        {
            $user = Auth::user();

            // Find the order panel with relationships of.....
            $order_panel = OrderPanel::where('id', $order_panel_id)
                ->with(['panel', 'order.orderInfo'])
                ->first();

            if (!$order_panel) {
                return response()->json(['message' => 'Order panel not found.'], 404);
            }

            $order_panel_split = OrderPanelSplit::where('order_panel_id', $order_panel->id)
                ->where('order_id', $order_panel->order_id)
                ->first();

            if (!$order_panel_split) {
                return response()->json(['message' => 'Order panel split not found.'], 404);
            }

            // Check if this panel is already assigned to another user
            $existingAssignment = UserOrderPanelAssignment::where([
                'order_panel_id' => $order_panel->id,
                'order_id' => $order_panel->order_id,                       
                'order_panel_split_id' => $order_panel_split->id,
            ])->first();

            if ($existingAssignment && $existingAssignment->contractor_id !== $user->id) {
                return response()->json(['message' => 'This panel is already assigned to another user.'], 403);
            }

            // Create or update the assignment
            UserOrderPanelAssignment::updateOrCreate(
                [
                    'order_panel_id' => $order_panel->id,
                    'contractor_id' => $user->id,
                ],
                [
                    'order_id' => $order_panel->order_id,
                    'order_panel_split_id' => $order_panel_split->id,
                ]
            );

            // Update the status of the order panel (example: to "assigned")
            $order_panel->status = 'assigned'; // or a status code like 1
            $order_panel->save();

            return response()->json(['message' => 'Panel assigned successfully.'], 200);
            }


      public function showAssingedSplitDetail(Request $request, $assigned_panel_id ){
            $assignedPanel=UserOrderPanelAssignment::where('id',$assigned_panel_id)->first();
            if (!$assignedPanel) {
                return response()->json(['message' => 'Assigned panel not found.'], 404);
            }
            $orderPanel = OrderPanel::with(['panel', 'order.orderInfo'])
                ->where('id', $assignedPanel->order_panel_id)
                ->first();

            if (!$orderPanel) {
                return response()->json(['message' => 'Order panel not found.'], 404);
            }

            $orderPanelSplit = OrderPanelSplit::where('id', $assignedPanel->order_panel_split_id)
                ->first();

            if(!$orderPanelSplit){
            return repsonse()->json(['message' => 'Order panel split not found.'], 404);
            }else{
                return response()->json(['orderPanelSplit'=>$orderPanelSplit],200);
            }

            
        }

        //mark panel as completed
 public function markOrderPanelAsStatus(Request $request, $assigned_panel_id)
 {
    $assignedPanel = UserOrderPanelAssignment::where('id', $assigned_panel_id)->first();
    if (!$assignedPanel) {
        return response()->json(['message' => 'Assigned panel not found.'], 404);
    }

    // Update the status of the order panel to 'completed'
    $orderPanel = OrderPanel::find($assignedPanel->order_panel_id);
    if (!$orderPanel) {
        return response()->json(['message' => 'Order panel not found.'], 404);
    }

    $orderPanel->status = 'completed'; // or a status code like 2
    $orderPanel->save();

    return response()->json(['message' => 'Panel marked as completed successfully.'], 200);

 }

    public function getPanelById($id)
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

    public function getOrderPanelById($id)
    {
        try {
            $orderPanel = OrderPanel::with(['panel', 'order.orderInfo'])->findOrFail($id);
            return response()->json($orderPanel);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Order panel not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve order panel'], 500);
        }
    }

    public function getOrderPanelSplitById($id)
    {
        try {
            $orderPanelSplit = OrderPanelSplit::with(['orderPanel', 'orderPanel.order'])->findOrFail($id);
            return response()->json($orderPanelSplit);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Order panel split not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve order panel split'], 500);
        }
    }
    public function getUserOrderPanelAssignmentById($id)
    {
        try {
            $assignment = UserOrderPanelAssignment::with(['orderPanel', 'orderPanelSplit'])->findOrFail($id);
            return response()->json($assignment);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'User order panel assignment not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve user order panel assignment'], 500);
        }
    }


    public function getAssignedPanelsByUserId($userId)
    {
        try {
            $assignments = UserOrderPanelAssignment::with(['orderPanel', 'orderPanelSplit'])
                ->where('contractor_id', $userId)
                ->get();

            return response()->json($assignments);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to retrieve assigned panels'], 500);
        }
    }

    
}
