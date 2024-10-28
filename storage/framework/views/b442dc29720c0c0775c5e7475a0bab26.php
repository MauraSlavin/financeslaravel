<html>
    <head>
        <link rel="stylesheet" href="<?php echo e(asset('css/styles.css')); ?>">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script> -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>

    <body>
    <h1>Assets</h1>

        <table>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Amount</th>
                    <th>Last Balanced</th>
                    <th>Invest or Trans</th>
                </tr>
            </thead>
          
            <tbody>
                <?php $__currentLoopData = $accounts; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $account): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <?php if($account->account != null): ?>
                        <tr>
                            <td><?php echo e($account->account); ?></td>
                            <td><?php echo e($account->amount); ?></td>
                            <td><?php echo e($account->max_last_balanced); ?></td>
                            <td><?php echo e($account->type); ?></td>
                        </tr>
                    <?php endif; ?>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>

        </table>
    </body>
</html><?php /**PATH C:\Users\maura\financesLaravel\resources\views/assets.blade.php ENDPATH**/ ?>