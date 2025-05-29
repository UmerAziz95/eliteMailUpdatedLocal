<!DOCTYPE html>
<html>
<head>
    <title>Subscription Checkout</title>
    <style>
        html, body {
            margin: 0;
            padding: 0;
            height: 100%;
        }
        iframe {
            width: 100%;
            height: 100%;
            border: none;
        }
    </style>
</head>
<body>
    <iframe id="checkout-frame" data-g-quantity="5"></iframe>
<!-- jquery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    // subscribe plan
    // function subscribePlan(planId) {
    //     $.ajax({
    //         url: `/customer/plans/${planId}/subscribe`,
    //         type: 'POST',
    //         data: {
    //             _token: '{{ csrf_token() }}',
    //         },
    //         success: function(response) {
    //             if (response.success) {
    //                 const iframe = document.getElementById('checkout-frame');
    //                 iframe.src = response.hosted_page_url;
                    
    //                 iframe.addEventListener('load', function() {
    //                     try {
    //                         const doc = iframe.contentDocument || iframe.contentWindow?.document;
    //                         if (doc) {
    //                             const quantityElement = doc.querySelector('.g-quantity');
    //                             if (quantityElement) {
    //                                 quantityElement.innerHTML = '';
    //                             } else {
    //                                 console.warn('Element .g-quantity not found in iframe.');
    //                             }
    //                         } else {
    //                             console.warn('Iframe document is not accessible (possibly cross-origin).');
    //                         }
    //                     } catch (e) {
    //                         console.warn('Could not modify iframe content:', e);
    //                     }
    //                 });

    //             } else {
    //                 // Show error message
    //                 alert(response.message || 'Failed to initiate subscription');
    //             }
    //         },
    //         error: function(xhr) {
    //             alert(xhr.responseJSON?.message || 'Failed to initiate subscription');
    //         }
    //     });
    // }
    // redirect to chargebee checkout
    // subscribe plan function
    function subscribePlan(planId) {
        $.ajax({
            url: `/customer/plans/${planId}/subscribe`,
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
            },
            success: function(response) {
                if (response.success) {
                    // Redirect to Chargebee hosted page
                    window.location.href = response.hosted_page_url;
                } else {
                    // Show error message
                    toastr.error(response.message || 'Failed to initiate subscription');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to initiate subscription');
            }
        });
    }
    subscribePlan({{ $plan->id }});
});
</script>

</body>
</html>
