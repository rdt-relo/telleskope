<style>
    .add-amount {
    width: 260px;
    border-radius: 7px;
}
.donation-heading {
    font-weight: bold;
    margin-right: 14px;
}
.card {
    margin-top: 30px;
}
.dontation-text{
    margin-bottom: 20px;
}
.add-amount{
    padding-left:10px;
}
</style>
<div class="container inner-background">
    <div class="row row-no-gutters w-100">
    <div class="col-md-12 col-xs-12">
        <div class="inner-page-title">
            <h1><?php echo $documentTitle = gettext('Donate').' - '. $group->val('groupname');?></h1>
        </div>
    </div>
    <hr class="lineb">
   
    <div class="col-md-12 p-0 dontation-text"> 
    <p>
    <?= gettext('We highly respect your privacy and are committed to protecting it through our compliance with this Privacy Policy and all applicable laws, including the U.S. federal and state laws, as well as, if and where applicable, the EU General Data Protection Regulation 2016/679 (the "GDPR"). Under the GDPR, you may have certain rights that you will be able to exercise in a way set out in this Privacy Policy.'); ?>
</p></div>
<div class="col-md-12 p-0">     
<div class="input">
<div class="row d-flex justify-content-center">    
    <div class="col-sm-12"> 
    <h6><strong>Credit Card Donation</strong></h6> 
            <div class="d-flex justify-content-center mt-4"> 
                <label><span class="hidden-xs">
                        <h6 class="donation-heading"><?= gettext('Enter Your Donation');?></h6>
                    </span></label>
                <div class="d-flex justify-content-center"> 
                <input name="donation_amount" class="add-amount" type="number" step="0.01" required>
                <div class="addition">&nbsp;$</div>
            </div>
            </div>
        </div>  
            <div class="card ">
                <div class="card-header">
                    <div class="bg-white shadow-sm pt-4 pl-2 pr-2 pb-2">
                        <!-- Credit card form tabs -->
                        <ul role="tablist" class="nav bg-light nav-pills rounded nav-fill mb-3">
                            <li class="nav-item"> <a data-toggle="pill" href="#credit-card" class="nav-link active "> <i class="fas fa-credit-card mr-2"></i> <?= gettext('Credit Card');?> </a> </li> <span style="margin-top:18px;"> <?= gettext('OR');?> </span>   
                            <li class="nav-item"> <a data-toggle="pill" href="#debit-card" class="nav-link active "> <i class="fas fa-credit-card mr-2"></i> <?= gettext('Debit Card');?>  </a> </li>                      
                        </ul>
                    </div> <!-- End -->
                    <!-- Credit card form content -->
                    <div class="tab-content">
                        <!-- credit card info-->
                        <div id="credit-card" class="tab-pane fade show active pt-3">
                            <form role="form" method="post" onsubmit="event.preventDefault()">
                            <div class="form-group"> <label for="username">
                                       
                                    </label> <input type="email" name="email" placeholder="Email" required class="form-control "> </div>
                                <div class="form-group"> <label for="username">
                                       
                                    </label> <input type="text" name="username" placeholder="Name on the card" required class="form-control "> </div>
                                <div class="form-group"> <label for="cardNumber">
                                       
                                    </label>
                                    <div class="input-group"> <input type="text" name="cardNumber" placeholder="Valid card number" class="form-control " required>
                                        <div class="input-group-append"> <span class="input-group-text text-muted"> <i class="fab fa-cc-visa mx-1"></i> <i class="fab fa-cc-mastercard mx-1"></i> <i class="fab fa-cc-amex mx-1"></i> </span> </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-sm-8">
                                        <div class="form-group"> <label><span class="hidden-xs">
                                                    <h6><?= gettext('Expiration Date');?></h6>
                                                </span></label>
                                            <div class="input-group"> <input type="number" placeholder="MM" name="" class="form-control" required min="1" max="12"> <input type="number" placeholder="YY" name="" class="form-control" required min="23"> </div>
                                        </div>
                                    </div>
                                    <div class="col-sm-4">
                                        <div class="form-group mb-4"> <label data-toggle="tooltip" title="Three digit CV code on the back of your card">
                                                <h6> <?= gettext('CVV');?> <i class="fa fa-question-circle d-inline"></i></h6>
                                            </label> <input type="text" required class="form-control" pattern="\d{3,4}"> </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="input-group">
                                             <label>
                                                 <span class="hidden-xs">
                                                    <strong><?= gettext('Your Donation :');?> $ <span id="donation-amount"> 0.0</span></strong>
                                                </span>
                                             </label>


                                        </div>
                                    </div>                                   
                                </div>
                                <div class="card-footer" style="margin-top: 20px;"> <button type="submit" class="subscribe btn btn-primary btn-block shadow-sm"> <?= gettext('Confirm Payment');?> </button>
                            </form>
                        </div>
                    </div> <!-- End -->                    
                </div>
            
        </div>
    </div>

    <div class="col-sm-12 mt-5">
    <h6><strong> Employee Payroll Contribution</strong></h6>  
            <div class="d-flex justify-content-center mt-4"> 
                <label><span class="hidden-xs">
                        <h6 class="donation-heading"><?= gettext('Enter Your Payroll Contribution Amount');?></h6>
                    </span></label>
                <div class="d-flex justify-content-center"> 
                <input class="add-amount" type="number" name="contribution_amount" step="0.01" required>
                <div class="addition">&nbsp;$</div>
            </div>
            </div>
        </div>  
            <div class="card-header card mt-3 mb-5">
                <!-- Credit card form content -->
                    <div class="tab-content">
                        <!-- credit card info-->
                        <div id="credit-card" class="tab-pane fade show active pt-3">
                            <form role="form" method="post" onsubmit="event.preventDefault()">
                            <div class="form-group"> 
                                <label for="username"> </label> 
                                    <input id="startDate" type="text" name="start_date" placeholder="Start Date" required class="form-control "> 
                                </div>
                                <div class="form-group"> 
                                    <label for="username">                                       
                                    </label> <input id="endDate" type="text" name="end_date" placeholder="End Date" required class="form-control ">
                                 </div>
                             

                                <div class="row">
                                    <div class="col-sm-12">
                                        <div class="input-group">
                                             <label>
                                                 <span class="hidden-xs">
                                                    <strong><?= gettext('Your Total Monthly Donation :');?> $ <span id="contribution-amount">200</span></strong>
                                                </span>
                                             </label>


                                        </div>
                                    </div>                                   
                                </div>
                                <div class="card-footer" style="margin-top: 20px;"> <button type="submit" class="subscribe btn btn-primary btn-block shadow-sm"> <?= gettext('Confirm Payroll Contribution');?> </button>
                            </form>
                        </div>
                    </div> <!-- End -->                    
                            
        </div>
    </div>
    </div>

<script>
$("input[name=donation_amount]").on('keyup', function () {
    $('#donation-amount').html($(this).val());
});

$(function() {
    $('[data-toggle="tooltip"]').tooltip()
})
</script>
<script type="text/javascript">
    $(document).ready(function () {
        $('#startDate').datepicker({
            format: "dd/mm/yyyy"
        });
        $('#endDate').datepicker({
            format: "dd/mm/yyyy"
        });
    });

    updatePageTitle('<?= addslashes($documentTitle); ?>');
</script>