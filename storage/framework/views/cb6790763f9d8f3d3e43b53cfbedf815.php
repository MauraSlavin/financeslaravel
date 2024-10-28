<html>
    <head>
        <link rel="stylesheet" href="<?php echo e(asset('css/styles.css')); ?>">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Accounts</h1>
    <!-- <a href="<?php echo e(route('assets')); ?>" class="btn btn-primary">Assets</a> -->
    <button type="button" id="assets" class="btn btn-primary">Assets</button>

        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Cleared</th>
                    <th>Register</th>
                    <th>Last Balanced</th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <tr>
                        <td><?php echo e($account->account); ?></td>
                        <td><?php echo e($account->cleared); ?></td>
                        <td><?php echo e($account->register); ?></td>
                        <td><?php echo e($account->max_last_balanced); ?></td>
                        <td>
                            <a href="<?php echo e(route('transactions',['accountName' => $account->account, 'clearedBalance' => $account->cleared, 'registerBalance' => $account->register, 'lastBalanced' => $account->max_last_balanced])); ?>" class="btn btn-primary btn-sm">Transactions</a>
                        </td>
                        <td>
                            <a href="<?php echo e(route('balances',['accountName' => $account->account])); ?>" class="btn btn-primary btn-sm">Balances</a>
                        </td>
                    </tr>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>

        <script>

            $(document).ready(function() {

                $('#assets').on('click', function(e) {
                    e.preventDefault();


                    const url = '/accounts/assets';
                    window.location.href = url;
                });
            });

        </script>

    </body>
</html><?php /**PATH C:\Users\maura\financesLaravel\resources\views/accounts.blade.php ENDPATH**/ ?>