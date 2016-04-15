<?php require_once 'include/functions.php'; ?>
<?php load_environment_variables(); ?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Connection Status</title>
    <link rel="stylesheet" href="css/semantic.min.css">
    <style>
        body { margin: 1em; }
    </style>
</head>
<body>
<div class="ui equal width stackable grid container">
    <?php $connections = get_distinct_connections(); ?>
    <?php foreach($connections as $connection): ?>
    <div class="column">
        <div class="ui segments">
            <div class="ui center aligned padded text segment container">
                <h2 class="ui header">Conexão <?php echo $connection; ?></h2>
                <?php $last_failure = get_last_failure_for_connection($connection); ?>
                <?php if(is_null($last_failure['end'])): ?>
                    <?php $duration_of_current_disconnection = get_durantion_of_disconnection($last_failure['start']); ?>
                    <p>Estamos sem conexão com a <?php echo $connection; ?> há <span class="ui blue small label"><?php echo $duration_of_current_disconnection;?></span>!</p>
                <?php else: ?>
                    <p>Estamos trabalhando há <span class="ui blue small label"><?php echo get_days_since_date($last_failure['end']); ?></span> dias sem uma queda de conexão da <?php echo $connection; ?></p>
                <?php endif; ?>
            </div>
            <div class="ui secondary blue segment">
                <div class="ui header">Últimas falhas registradas</div>
                <?php $last_failures = get_last_closed_failures_for_connection($connection); ?>
                <table class="ui compact small celled striped table">
                    <?php if(empty($last_failures)): ?>
                    <?php else: ?>
                    <thead>
                        <tr>
                            <th>Início</th>
                            <th>Fim</th>
                            <th>Tempo Desconectado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($last_failures as $failure): ?>
                        <tr>
                            <td><?php echo date(getenv('DATE_FORMAT'), strtotime($failure['start'])); ?></td>
                            <td><?php echo date(getenv('DATE_FORMAT'), strtotime($failure['end'])); ?></td>
                            <td><?php echo get_durantion_of_disconnection($failure['start'], $failure['end']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>