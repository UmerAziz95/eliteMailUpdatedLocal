function updatePlanCard(plan) {
    const planCard = $(`#plan-${plan.id}`);
    planCard.find('.plan-name').text(plan.name);
    planCard.find('.plan-price').html(`$${parseFloat(plan.price).toFixed(2)} <span class="fs-6">/${plan.duration}</span>`);
    planCard.find('.plan-description').text(plan.description);
    
    // Update features
    const featuresList = planCard.find('.features-list');
    featuresList.empty();
    
    if (plan.features.projects) {
        featuresList.append(`<li class="mb-2"><i class="fas fa-check text-success"></i> ${plan.features.projects} Projects</li>`);
    }
    if (plan.features.storage) {
        featuresList.append(`<li class="mb-2"><i class="fas fa-check text-success"></i> ${plan.features.storage} Storage</li>`);
    }
    if (plan.features.support) {
        featuresList.append(`<li class="mb-2"><i class="fas fa-check text-success"></i> ${plan.features.support} Support</li>`);
    }
    if (plan.features.custom_domain !== undefined) {
        featuresList.append(`
            <li class="mb-2">
                <i class="fas ${plan.features.custom_domain ? 'fa-check text-success' : 'fa-times text-danger'}"></i> 
                Custom Domain
            </li>
        `);
    }
}

document.addEventListener("DOMContentLoaded", function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.forEach(function(tooltipTriggerEl) {
        new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Handle add plan form submission
    $('#addPlan form').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: form.serialize(),
            success: function(response) {
                if (response.success) {
                    $('#addPlan').modal('hide');
                    toastr.success(response.message);
                    // For new plans, we still need to reload the page
                    setTimeout(function() {
                        window.location.reload();
                    }, 1000);
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    Object.keys(errors).forEach(function(key) {
                        toastr.error(errors[key][0]);
                    });
                } else {
                    toastr.error('Something went wrong. Please try again.');
                }
            }
        });
    });

    // Handle edit plan form submissions
    $('form[action*="plans/"][method="POST"]:not([data-delete])').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);
        
        Swal.fire({
            title: 'Update Plan',
            text: 'Are you sure you want to update this plan?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#7367f0',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, update it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            form.closest('.modal').modal('hide');
                            toastr.success(response.message);
                            // Update the plan card with new data
                            updatePlanCard(response.plan);
                        }
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            var errors = xhr.responseJSON.errors;
                            Object.keys(errors).forEach(function(key) {
                                toastr.error(errors[key][0]);
                            });
                        } else {
                            toastr.error('Something went wrong. Please try again.');
                        }
                    }
                });
            }else{
                // enable the button again
                form.find('button[type="submit"]').removeClass('btn-loading').prop('disabled', false);
            }
        });
    });

    // Handle delete plan
    $('form[action*="plans/"][method="POST"][data-delete]').on('submit', function(e) {
        e.preventDefault();
        var form = $(this);

        Swal.fire({
            title: 'Delete Plan',
            text: 'Are you sure you want to delete this plan?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: form.attr('action'),
                    type: 'POST',
                    data: form.serialize(),
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            form.closest('.col-md-4').fadeOut(300, function() {
                                $(this).remove();
                            });
                        }
                    },
                    error: function() {
                        toastr.error('Something went wrong. Please try again.');
                    }
                });
            }else{
                // enable the button again
                form.find('button[type="submit"]').removeClass('btn-loading').prop('disabled', false);
            }
        });
    });
});

$(document).ready(function() {
    var table = $('#myTable').DataTable();

    $(".dt-search").append(
        '<button id="addNew" data-bs-target="#addRoleModal" data-bs-toggle="modal" class="m-btn rounded-1 border-0 ms-2" style="padding: .4rem 1.4rem"><i class="fa-solid fa-plus"></i> Add Permission</button>'
    );
});