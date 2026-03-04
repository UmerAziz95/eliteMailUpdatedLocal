# Manual Panel Assignment for Pool Creation - Implementation Plan

## Overview
Add a new section to the pool creation/edit form that allows admins to manually assign pools to panels in batches, bypassing the automatic `pool:assigned-panel` command. This gives admins control over which panels receive which pool segments while maintaining the existing automatic assignment as an option.

---

## Implementation Steps

### 1. Create New Service Class for Manual Panel Assignment

**File:** `app/Services/ManualPanelAssignmentService.php`

**Purpose:** Handle all manual panel assignment logic separately from the automatic command.

**Key Methods:**
- `validateManualAssignments($pool, $assignments)` - Validate batch assignments before processing
- `processManualAssignments($pool, $assignments)` - Main orchestration method
- `assignBatchToPanel($pool, $batch, $panelId)` - Assign a single batch to a panel
- `calculateBatchSpace($domains, $inboxesPerDomain)` - Calculate required space
- `verifyPanelCapacity($panelId, $spaceNeeded)` - Check if panel has capacity
- `createPoolPanelSplit($pool, $panel, $domains, $space, $splitNumber)` - Create split record
- `updatePanelLimits($panel, $space)` - Update panel capacity
- `rollbackManualAssignments($pool)` - Rollback on failure
- `getAvailablePanels($providerType)` - Get panels with available capacity
- `validateAllDomainsAssigned($pool, $assignments)` - Ensure complete assignment

**Validation Rules:**
- All domains must be assigned (no gaps)
- No domain can be assigned twice
- Panel must have sufficient capacity
- Panel must match pool's provider type
- Inboxes per domain must match pool configuration
- Domain IDs must exist in pool

---

### 2. Update PoolController

**File:** `app/Http/Controllers/Admin/PoolController.php`

**Changes to `store()` method:**
```php
public function store(Request $request)
{
    // ... existing validation and pool creation ...
    
    // Check if manual assignment data is provided
    if ($request->has('manual_assignments') && !empty($request->manual_assignments)) {
        // Use manual assignment service
        $assignmentService = new ManualPanelAssignmentService();
        $assignmentService->processManualAssignments($pool, $request->manual_assignments);
    } else {
        // Use existing automatic assignment
        Artisan::call('pool:assigned-panel', [
            '--provider' => $pool->provider_type
        ]);
    }
    
    // ... rest of the method ...
}
```

**Changes to `update()` method:**
```php
public function update(Request $request, Pool $pool)
{
    // ... existing validation and pool update ...
    
    // If manual assignments are provided and domains changed
    if ($request->has('manual_assignments') && !empty($request->manual_assignments)) {
        // Rollback existing assignments
        $assignmentService = new ManualPanelAssignmentService();
        $assignmentService->rollbackManualAssignments($pool);
        
        // Apply new manual assignments
        $assignmentService->processManualAssignments($pool, $request->manual_assignments);
    } elseif ($request->has('reassign_automatically')) {
        // Re-run automatic assignment if requested
        Artisan::call('pool:assigned-panel', [
            '--provider' => $pool->provider_type
        ]);
    }
    
    // ... rest of the method ...
}
```

**New AJAX Endpoints:**
```php
// Get available panels for provider type
public function getAvailablePanels(Request $request)
{
    $providerType = $request->provider_type;
    $assignmentService = new ManualPanelAssignmentService();
    $panels = $assignmentService->getAvailablePanels($providerType);
    
    return response()->json([
        'success' => true,
        'panels' => $panels
    ]);
}

// Validate manual assignments before submission
public function validateManualAssignments(Request $request)
{
    $poolData = $request->pool_data;
    $assignments = $request->manual_assignments;
    
    $assignmentService = new ManualPanelAssignmentService();
    $validation = $assignmentService->validateManualAssignments($poolData, $assignments);
    
    return response()->json($validation);
}

// Get panel capacity info
public function getPanelCapacity($panelId)
{
    $panel = PoolPanel::findOrFail($panelId);
    
    return response()->json([
        'success' => true,
        'panel' => [
            'id' => $panel->id,
            'title' => $panel->title,
            'auto_generated_id' => $panel->auto_generated_id,
            'limit' => $panel->limit,
            'used_limit' => $panel->used_limit,
            'remaining_limit' => $panel->remaining_limit,
            'provider_type' => $panel->provider_type
        ]
    ]);
}
```

---

### 3. Add Manual Assignment UI Section to Pool Form

**File:** `resources/views/admin/pools/form.blade.php`

**Location:** After the "Additional Assets" section (around line 3700+)

**New Section Structure:**

```blade
{{-- Manual Panel Assignment Section --}}
<div class="card mb-4" id="manual-assignment-section" style="display: none;">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-server me-2"></i>Manual Panel Assignment
        </h5>
    </div>
    <div class="card-body">
        {{-- Assignment Mode Toggle --}}
        <div class="mb-4">
            <label class="form-label fw-bold">Assignment Mode</label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="assignment_mode" id="mode_automatic" value="automatic" checked>
                <label class="btn btn-outline-primary" for="mode_automatic">
                    <i class="fas fa-magic me-1"></i>Automatic Assignment
                </label>
                
                <input type="radio" class="btn-check" name="assignment_mode" id="mode_manual" value="manual">
                <label class="btn btn-outline-primary" for="mode_manual">
                    <i class="fas fa-hand-pointer me-1"></i>Manual Assignment
                </label>
            </div>
            <small class="text-muted d-block mt-2">
                <strong>Automatic:</strong> System assigns pools to panels automatically based on capacity.<br>
                <strong>Manual:</strong> You specify which domains go to which panels.
            </small>
        </div>

        {{-- Manual Assignment Builder --}}
        <div id="manual-assignment-builder" style="display: none;">
            {{-- Assignment Summary --}}
            <div class="alert alert-info mb-3">
                <div class="row">
                    <div class="col-md-3">
                        <strong>Total Domains:</strong> <span id="total-domains-count">0</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Assigned:</strong> <span id="assigned-domains-count">0</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Remaining:</strong> <span id="remaining-domains-count">0</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Total Space:</strong> <span id="total-space-needed">0</span> inboxes
                    </div>
                </div>
            </div>

            {{-- Batch List --}}
            <div id="batches-container">
                {{-- Batches will be added here dynamically --}}
            </div>

            {{-- Add Batch Button --}}
            <button type="button" class="btn btn-success" id="add-batch-btn">
                <i class="fas fa-plus me-1"></i>Add Batch
            </button>

            {{-- Validation Messages --}}
            <div id="assignment-validation-messages" class="mt-3"></div>
        </div>
    </div>
</div>

{{-- Batch Template (Hidden) --}}
<template id="batch-template">
    <div class="card mb-3 batch-item" data-batch-index="">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Batch <span class="batch-number"></span></h6>
            <button type="button" class="btn btn-sm btn-danger remove-batch-btn">
                <i class="fas fa-trash"></i>
            </button>
        </div>
        <div class="card-body">
            <div class="row">
                {{-- Panel Selection --}}
                <div class="col-md-4">
                    <label class="form-label">Panel</label>
                    <select class="form-select panel-select" name="manual_assignments[][panel_id]" required>
                        <option value="">Select Panel...</option>
                    </select>
                    <small class="text-muted panel-capacity-info"></small>
                </div>

                {{-- Domain Range Selection --}}
                <div class="col-md-3">
                    <label class="form-label">Start Domain</label>
                    <input type="number" class="form-control domain-start" name="manual_assignments[][domain_start]" min="1" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">End Domain</label>
                    <input type="number" class="form-control domain-end" name="manual_assignments[][domain_end]" min="1" required>
                </div>

                {{-- Batch Summary --}}
                <div class="col-md-2">
                    <label class="form-label">Space Needed</label>
                    <input type="text" class="form-control batch-space-display" readonly>
                    <input type="hidden" class="batch-space-value" name="manual_assignments[][space_needed]">
                </div>
            </div>

            {{-- Domain List Preview --}}
            <div class="mt-2">
                <small class="text-muted">
                    <strong>Domains:</strong> <span class="domain-count-display">0</span> domains
                    (<span class="domain-range-display">-</span>)
                </small>
            </div>

            {{-- Validation Status --}}
            <div class="batch-validation-status mt-2"></div>
        </div>
    </div>
</template>
```

**JavaScript for Manual Assignment:**

```javascript
// Location: In the existing <script> section of form.blade.php

// Manual Assignment Variables
let batchIndex = 0;
let totalDomains = 0;
let inboxesPerDomain = 1;
let providerType = 'Google';
let availablePanels = [];

// Initialize Manual Assignment
function initializeManualAssignment() {
    // Toggle between automatic and manual mode
    $('input[name="assignment_mode"]').on('change', function() {
        if ($(this).val() === 'manual') {
            $('#manual-assignment-builder').slideDown();
            loadAvailablePanels();
        } else {
            $('#manual-assignment-builder').slideUp();
        }
    });

    // Add Batch Button
    $('#add-batch-btn').on('click', function() {
        addBatch();
    });

    // Remove Batch Button (delegated)
    $('#batches-container').on('click', '.remove-batch-btn', function() {
        $(this).closest('.batch-item').remove();
        updateBatchNumbers();
        updateAssignmentSummary();
    });

    // Panel Selection Change
    $('#batches-container').on('change', '.panel-select', function() {
        const panelId = $(this).val();
        const batchCard = $(this).closest('.batch-item');
        
        if (panelId) {
            loadPanelCapacity(panelId, batchCard);
        }
    });

    // Domain Range Change
    $('#batches-container').on('input', '.domain-start, .domain-end', function() {
        const batchCard = $(this).closest('.batch-item');
        updateBatchCalculations(batchCard);
    });

    // Update when inboxes per domain changes
    $('#inboxes_per_domain').on('change', function() {
        inboxesPerDomain = parseInt($(this).val()) || 1;
        updateAllBatchCalculations();
    });

    // Update when domains are added/removed
    $('#domains').on('input', debounce(function() {
        updateDomainCount();
    }, 500));
}

// Load Available Panels
function loadAvailablePanels() {
    providerType = $('select[name="provider_type"]').val() || 'Google';
    
    $.ajax({
        url: '{{ route("admin.pools.getAvailablePanels") }}',
        method: 'GET',
        data: { provider_type: providerType },
        success: function(response) {
            if (response.success) {
                availablePanels = response.panels;
                updatePanelDropdowns();
            }
        },
        error: function() {
            showError('Failed to load available panels');
        }
    });
}

// Update Panel Dropdowns
function updatePanelDropdowns() {
    const $selects = $('.panel-select');
    
    $selects.each(function() {
        const currentValue = $(this).val();
        $(this).empty().append('<option value="">Select Panel...</option>');
        
        availablePanels.forEach(panel => {
            const optionText = `${panel.auto_generated_id} - ${panel.title} (${panel.remaining_limit}/${panel.limit} available)`;
            $(this).append(`<option value="${panel.id}">${optionText}</option>`);
        });
        
        if (currentValue) {
            $(this).val(currentValue);
        }
    });
}

// Load Panel Capacity Info
function loadPanelCapacity(panelId, batchCard) {
    $.ajax({
        url: `/admin/pools/panels/${panelId}/capacity`,
        method: 'GET',
        success: function(response) {
            if (response.success) {
                const panel = response.panel;
                const capacityHtml = `
                    <i class="fas fa-info-circle me-1"></i>
                    Available: ${panel.remaining_limit} / ${panel.limit} inboxes
                `;
                batchCard.find('.panel-capacity-info').html(capacityHtml);
                validateBatch(batchCard);
            }
        }
    });
}

// Add New Batch
function addBatch() {
    const template = document.getElementById('batch-template');
    const clone = template.content.cloneNode(true);
    
    batchIndex++;
    const $batchItem = $(clone).find('.batch-item');
    $batchItem.attr('data-batch-index', batchIndex);
    $batchItem.find('.batch-number').text(batchIndex);
    
    // Populate panel dropdown
    const $panelSelect = $batchItem.find('.panel-select');
    availablePanels.forEach(panel => {
        const optionText = `${panel.auto_generated_id} - ${panel.title} (${panel.remaining_limit}/${panel.limit} available)`;
        $panelSelect.append(`<option value="${panel.id}">${optionText}</option>`);
    });
    
    $('#batches-container').append($batchItem);
    updateAssignmentSummary();
}

// Update Batch Numbers
function updateBatchNumbers() {
    $('.batch-item').each(function(index) {
        $(this).find('.batch-number').text(index + 1);
    });
}

// Update Batch Calculations
function updateBatchCalculations(batchCard) {
    const start = parseInt(batchCard.find('.domain-start').val()) || 0;
    const end = parseInt(batchCard.find('.domain-end').val()) || 0;
    
    if (start > 0 && end > 0 && end >= start) {
        const domainCount = end - start + 1;
        const spaceNeeded = domainCount * inboxesPerDomain;
        
        batchCard.find('.domain-count-display').text(domainCount);
        batchCard.find('.domain-range-display').text(`${start} - ${end}`);
        batchCard.find('.batch-space-display').val(spaceNeeded);
        batchCard.find('.batch-space-value').val(spaceNeeded);
        
        validateBatch(batchCard);
    }
    
    updateAssignmentSummary();
}

// Update All Batch Calculations
function updateAllBatchCalculations() {
    $('.batch-item').each(function() {
        updateBatchCalculations($(this));
    });
}

// Validate Single Batch
function validateBatch(batchCard) {
    const start = parseInt(batchCard.find('.domain-start').val()) || 0;
    const end = parseInt(batchCard.find('.domain-end').val()) || 0;
    const spaceNeeded = parseInt(batchCard.find('.batch-space-value').val()) || 0;
    const panelId = batchCard.find('.panel-select').val();
    
    let errors = [];
    
    // Validate domain range
    if (start < 1 || end < 1) {
        errors.push('Domain range must start from 1');
    }
    if (end < start) {
        errors.push('End domain must be >= start domain');
    }
    if (end > totalDomains) {
        errors.push(`End domain cannot exceed total domains (${totalDomains})`);
    }
    
    // Validate panel capacity
    if (panelId && spaceNeeded > 0) {
        const panel = availablePanels.find(p => p.id == panelId);
        if (panel && spaceNeeded > panel.remaining_limit) {
            errors.push(`Space needed (${spaceNeeded}) exceeds panel capacity (${panel.remaining_limit})`);
        }
    }
    
    // Display validation status
    const $statusDiv = batchCard.find('.batch-validation-status');
    if (errors.length > 0) {
        $statusDiv.html(`
            <div class="alert alert-danger alert-sm mb-0">
                <i class="fas fa-exclamation-triangle me-1"></i>
                ${errors.join('<br>')}
            </div>
        `);
    } else if (panelId && start > 0 && end > 0) {
        $statusDiv.html(`
            <div class="alert alert-success alert-sm mb-0">
                <i class="fas fa-check-circle me-1"></i>
                Valid assignment
            </div>
        `);
    } else {
        $statusDiv.empty();
    }
}

// Update Assignment Summary
function updateAssignmentSummary() {
    let assignedDomains = 0;
    const assignedRanges = [];
    
    $('.batch-item').each(function() {
        const start = parseInt($(this).find('.domain-start').val()) || 0;
        const end = parseInt($(this).find('.domain-end').val()) || 0;
        
        if (start > 0 && end >= start) {
            assignedDomains += (end - start + 1);
            assignedRanges.push([start, end]);
        }
    });
    
    const remaining = totalDomains - assignedDomains;
    const totalSpace = totalDomains * inboxesPerDomain;
    
    $('#assigned-domains-count').text(assignedDomains);
    $('#remaining-domains-count').text(remaining).toggleClass('text-danger', remaining !== 0);
    $('#total-space-needed').text(totalSpace);
    
    // Validate complete assignment
    if (remaining !== 0 && totalDomains > 0) {
        $('#assignment-validation-messages').html(`
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-1"></i>
                You have ${remaining} unassigned domains. All domains must be assigned.
            </div>
        `);
    } else {
        $('#assignment-validation-messages').empty();
    }
}

// Update Domain Count
function updateDomainCount() {
    const domainsText = $('#domains').val().trim();
    if (!domainsText) {
        totalDomains = 0;
    } else {
        const domains = domainsText.split(/[\n,]+/).map(d => d.trim()).filter(d => d);
        totalDomains = domains.length;
    }
    
    $('#total-domains-count').text(totalDomains);
    updateAssignmentSummary();
}

// Debounce Helper
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Show Manual Assignment Section (when editing existing pool)
function showManualAssignmentSection() {
    $('#manual-assignment-section').slideDown();
    initializeManualAssignment();
}

// Initialize on Document Ready
$(document).ready(function() {
    initializeManualAssignment();
    
    // Show section when pool is being created/edited
    @if(isset($pool))
        showManualAssignmentSection();
    @endif
});
```

---

### 4. Add Routes for AJAX Endpoints

**File:** `routes/web.php`

```php
// Manual Panel Assignment Routes
Route::prefix('admin/pools')->name('admin.pools.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('available-panels', [PoolController::class, 'getAvailablePanels'])->name('getAvailablePanels');
    Route::post('validate-assignments', [PoolController::class, 'validateManualAssignments'])->name('validateManualAssignments');
    Route::get('panels/{panel}/capacity', [PoolController::class, 'getPanelCapacity'])->name('getPanelCapacity');
});
```

---

### 5. Database Considerations

**No new migrations needed** - The existing schema supports manual assignment:
- `pool_panel_splits` table already stores assignments
- `pool_panels` table already tracks capacity
- No additional columns required

---

## Key Features

### Assignment Mode Toggle
- **Automatic Mode (Default):** Uses existing `pool:assigned-panel` command
- **Manual Mode:** Admin controls the assignment process

### Batch Builder Interface
- Add/remove batches dynamically
- Specify panel for each batch
- Define domain range (start/end)
- Real-time space calculation
- Visual validation feedback

### Validation & Safety
- Ensures all domains are assigned
- Prevents over-assignment to panels
- Checks panel capacity before submission
- Validates domain ranges don't overlap
- Provider type matching
- Transaction-based rollback on failure

### Real-time Feedback
- Panel capacity display
- Assignment progress summary
- Domain count tracking
- Batch-level validation
- Visual success/error indicators

---

## User Experience Flow

### Creating New Pool with Manual Assignment

1. Admin fills out pool details (domains, inboxes per domain, provider type)
2. In "Manual Panel Assignment" section, selects "Manual Assignment" mode
3. Clicks "Add Batch" to create first assignment batch
4. Selects panel from dropdown (shows available capacity)
5. Specifies domain range (e.g., domains 1-119)
6. System calculates space needed and validates capacity
7. Repeats for remaining domains across multiple panels
8. System validates all domains are assigned before submission
9. On submit, `ManualPanelAssignmentService` processes assignments
10. Success message shows assignment summary

### Editing Existing Pool

1. Admin opens pool edit form
2. Manual assignment section shows current assignments (read-only summary)
3. Can choose to:
   - Keep existing assignments (no changes)
   - Reassign automatically (re-runs command)
   - Reassign manually (clears existing, builds new batches)
4. If reassigning manually, same batch builder interface appears
5. On submit, old assignments are rolled back and new ones applied

---

## Further Considerations

### 1. Assignment Mode Behavior
**Decision:** Manual assignment should be available both during creation and editing. The toggle allows flexible switching between modes.

### 2. Partial Assignment Handling
**Decision:** Require complete assignment in manual mode. The system validates that all domains are assigned before allowing submission. This ensures data integrity.

### 3. UI Interface Approach
**Decision:** Use a table-based batch builder (simpler to implement and maintain). Each batch is a card with panel selection, domain range inputs, and validation feedback. This provides clarity without overwhelming complexity.

### 4. Overlap Detection
**Implementation:** Add JavaScript validation to detect overlapping domain ranges across batches and prevent duplicate assignments.

### 5. Post-Creation Modification
**Integration:** The existing pool panel reassignment feature (from research findings) can be used for post-creation modifications. Manual assignment focuses on initial setup.

### 6. Assignment Preview
**Enhancement:** Before final submission, show a preview modal summarizing all batch assignments with panel details for admin review.

---

## Technical Architecture

```
User Input (Form)
    ↓
PoolController (store/update)
    ↓
Decision: Manual or Automatic?
    ↓
┌─────────────────┬──────────────────────┐
│   Automatic     │      Manual          │
│   Assignment    │      Assignment      │
├─────────────────┼──────────────────────┤
│ Artisan::call   │ ManualPanel          │
│ pool:assigned-  │ AssignmentService    │
│ panel           │ →processManual       │
│                 │  Assignments()       │
└─────────────────┴──────────────────────┘
    ↓                      ↓
Database (pool_panel_splits, pool_panels)
    ↓
Success Response
```

---

## File Changes Summary

### New Files
1. `app/Services/ManualPanelAssignmentService.php` - Main service class

### Modified Files
1. `app/Http/Controllers/Admin/PoolController.php` - Add manual assignment logic and AJAX endpoints
2. `resources/views/admin/pools/form.blade.php` - Add UI section and JavaScript
3. `routes/web.php` - Add new routes for AJAX endpoints

### No Changes Required
- Migration files (existing schema supports this)
- Models (existing relationships work)
- Automatic assignment command (remains independent)

---

## Testing Checklist

- [ ] Create pool with manual assignment (small pool, single panel)
- [ ] Create pool with manual assignment (large pool, multiple panels)
- [ ] Verify panel capacity updates correctly
- [ ] Test validation (incomplete assignment, over-capacity, invalid ranges)
- [ ] Edit existing pool and change assignments
- [ ] Switch between automatic and manual mode
- [ ] Test with Google provider
- [ ] Test with Microsoft 365 provider
- [ ] Verify rollback on assignment failure
- [ ] Check that automatic mode still works
- [ ] Validate domain ID preservation
- [ ] Test overlapping domain range detection
- [ ] Verify all domains assigned validation

---

## Benefits

1. **Admin Control:** Full control over which pools go to which panels
2. **Flexibility:** Choose between automatic (fast) or manual (precise) assignment
3. **Transparency:** Visual feedback on assignments and capacity
4. **Safety:** Comprehensive validation prevents errors
5. **Maintainability:** Separate service keeps code organized
6. **Compatibility:** Works alongside existing automatic system
7. **Scalability:** Batch-based approach handles pools of any size

---

## Next Steps

1. Create `ManualPanelAssignmentService.php` with all methods
2. Update `PoolController.php` with new methods and logic
3. Add UI section to `form.blade.php`
4. Add JavaScript functionality
5. Add routes to `web.php`
6. Test thoroughly with different scenarios
7. Document usage for admin users
