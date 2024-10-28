<html>
    <head>
        <link rel="stylesheet" href="<?php echo e(asset('css/styles.css')); ?>">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    </head>

    <body id="addTransaction">

        <!-- include common functions -->
        <!-- <script src="<?php echo e(asset('js/commonFunctions.js')); ?>"></script> -->


        <!-- headers -->
        <h1>New Transaction</h1>
        <h6>* Required</h6>
        <!-- <button type="button" id="saveTransaction" class="btn btn-success rounded-sm">Save Transaction</button> -->

        <!-- fields for a new transaction -->
        <form action="<?php echo e(route('writeTransaction')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="form-row">
                <label class="newtranslabel" for="trans_date">Trans Date: *</label>
                <input class="newtransinput" type="text" id="trans_date" name="trans_date" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="clear_date">Clear Date:</label>
                <input class="newtransinput" type="clear_date" id="clear_date" name="clear_date" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="account">Account: *</label>
                <input class="newtransinput" type="account" id="account" name="account" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="toFrom">toFrom: *</label>
                <input class="newtransinput" type="toFrom" id="toFrom" name="toFrom" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="amount">Amount: *</label>
                <input class="newtransinput" type="amount" id="amount" name="amount" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="amtMike">Mike: *</label>
                <input class="newtransinput" type="amtMike" id="amtMike" name="amtMike" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="amtMaura">Maura: *</label>
                <input class="newtransinput" type="amtMaura" id="amtMaura" name="amtMaura" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="method">Method:</label>
                <input class="newtransinput" type="method" id="method" name="method" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="category">Category: *</label>
                <input class="newtransinput" type="category" id="category" name="category" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="tracking">Tracking:</label>
                <input class="newtransinput" type="tracking" id="tracking" name="tracking" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="stmtDate">Stmt Date: *</label>
                <input class="newtransinput" type="stmtDate" id="stmtDate" name="stmtDate" class="form-control" required>
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="total_amt">Total amt:</label>
                <input class="newtransinput" type="total_amt" id="total_amt" name="total_amt" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="total_key">Total Key:</label>
                <input class="newtransinput" type="total_key" id="total_key" name="total_key" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="bucket">Bucket:</label>
                <input class="newtransinput" type="bucket" id="bucket" name="bucket" class="form-control">
            </div>

            <div class="form-row">
                <label class="newtranslabel" for="notes">Notes:</label>
                <input class="newtransinput" type="notes" id="notes" name="notes" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Save Transaction</button>
        </form>
        <!-- <button type="button" style="margin-bottom: 20px;" id="saveTransaction" class="btn btn-success rounded-sm">Save Transaction</button> -->

        <script>
            
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
            

            $(document).ready(function() {
                

            });

        </script>
    </body>

</html><?php /**PATH C:\Users\maura\financesLaravel\resources\views/addtransaction.blade.php ENDPATH**/ ?>