$(document).ready(function() {
    const DIV_CARD = 'div.card';

    $('[data-toggle="tooltip"]').tooltip();

    $('[data-toggle="popover"]').popover({
        html: true
    });

    $('[data-toggle="card-remove"]').on('click', function(e) {
        e.preventDefault();

        let $card = $(this).closest(DIV_CARD);
        $card.remove();

        return false;
    });

    $('[data-toggle="card-collapse"]').on('click', function(e) {
        e.preventDefault();

        let $card = $(this).closest(DIV_CARD);
        $card.toggleClass('card-collapsed');

        return false;
    });


    $('[data-toggle="card-fullscreen"]').on('click', function(e) {
        e.preventDefault();

        let $card = $(this).closest(DIV_CARD);
        $card.toggleClass('card-fullscreen').removeClass('card-collapsed');

        return false;
    });
});
