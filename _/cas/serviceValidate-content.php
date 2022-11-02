<?php
header("Content-type: text/xml");
?>
<cas:serviceResponse xmlns:cas='http<?= core_secure::usingSSL() ? 's' : '' ?>://<?= $_SERVER['HTTP_HOST'] ?>/_/cas'>
    <?php
    $service = req_get::url('service', false);
    $ticket = req_get::txt('ticket');

    switch (mth_cas_ticket::validateTicket($ticket, $service)) {
        case mth_cas_ticket::VALIDATE_VALID:
            ?>
            <cas:authenticationSuccess>
                <cas:user><?= mth_cas_ticket::userIdentifier($ticket) ?></cas:user>
            </cas:authenticationSuccess>
            <?php
            break;
        case mth_cas_ticket::VALIDATE_INVALID_SERVICE:
            ?>
            <cas:authenticationFailure code="INVALID_SERVICE">
                <?= $service ?> is an invalid service
            </cas:authenticationFailure>
            <?php
            break;
        case mth_cas_ticket::VALIDATE_INVALID_TICKET:
            ?>
            <cas:authenticationFailure code="INVALID_TICKET">
                Ticket <?= $ticket ?> not recognized
            </cas:authenticationFailure>
            <?php
            break;
    }
    ?>
</cas:serviceResponse>