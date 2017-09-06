
<div class="row payment-plugin" id="gourlPayment" style="display: none;">
    <div class="col-xs-12 col-md-8 box-center center">
        
        <img class="img-responsive box-center center" src="{{ url('images/gourl/payment.png') }}" title="{{ trans('gourl::messages.Payment with Gourl(Bitcoin)') }}" style="margin-bottom: 20px;">
        
    </div>
</div>


@section('after_scripts')
    @parent
    <script>
        $(document).ready(function ()
        {
            var selectedPackage = $('input[name=package]:checked').val();
            var packagePrice = getPackagePrice(selectedPackage);
            var paymentMethod = $('#payment_method').find('option:selected').data('name');
    
            /* Check Payment Method */
            checkPaymentMethodForGourl(paymentMethod, packagePrice);
            
            $('#payment_method').on('change', function () {
                paymentMethod = $(this).find('option:selected').data('name');
                checkPaymentMethodForGourl(paymentMethod, packagePrice);
            });
            $('.package-selection').on('click', function () {

                selectedPackage = $(this).val();
                
                packagePrice = getPackagePrice(selectedPackage);
                paymentMethod = $('#payment_method').find('option:selected').data('name');
                checkPaymentMethodForGourl(paymentMethod, packagePrice);
            });
    
            /* Send Payment Request */
            $('#submitPostForm').on('click', function (e)
            {
                e.preventDefault();
        
                paymentMethod = $('#payment_method').find('option:selected').data('name');
                
                if (paymentMethod != 'gourl' || packagePrice <= 0) {
                    return false;
                }
    
                $('#postForm').submit();
        
                /* Prevent form from submitting */
                return false;
            });
        });

        function checkPaymentMethodForGourl(paymentMethod, packagePrice)
        {
            if (paymentMethod == 'gourl' && packagePrice > 0) {
                $('#gourlPayment').show();
            } else {
                $('#gourlPayment').hide();
            
            }
        }
    </script>
@endsection
