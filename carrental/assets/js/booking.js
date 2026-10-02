/* Live price estimate + date guards for the "Book Now" form on vehical-details.php.
   The server re-validates everything; this is only for a better experience. */
(function ($) {
  var $form = $('#booking-form');
  if (!$form.length) { return; }

  var pricePerDay = parseInt($form.data('price'), 10) || 0;
  var maxDays = parseInt($form.data('max-days'), 10) || 30;
  var currency = $form.data('currency') || '₹';
  var $from = $('#fromdate');
  var $to = $('#todate');
  var $summary = $('#booking-summary');

  function parse(value) {
    var parts = value.split('-');
    return parts.length === 3 ? Date.UTC(+parts[0], parts[1] - 1, +parts[2]) : NaN;
  }

  function update() {
    // Return date can never be before pick-up date.
    if ($from.val()) { $to.attr('min', $from.val()); }

    var from = parse($from.val() || '');
    var to = parse($to.val() || '');
    $summary.removeClass('is-error is-ok');

    if (isNaN(from) || isNaN(to)) {
      $summary.text('Select dates to see the estimated total.');
      return;
    }

    var days = Math.round((to - from) / 86400000) + 1;
    if (days < 1) {
      $summary.addClass('is-error').text('Return date must be on or after the pick-up date.');
    } else if (days > maxDays) {
      $summary.addClass('is-error').text('Bookings are limited to ' + maxDays + ' days.');
    } else {
      $summary.addClass('is-ok').html(
        days + ' day' + (days > 1 ? 's' : '') + ' &times; ' + currency + pricePerDay.toLocaleString() +
        '<strong>Estimated total: ' + currency + (days * pricePerDay).toLocaleString() + '</strong>'
      );
    }
  }

  $from.add($to).on('change input', update);
  $form.on('submit', function (event) {
    update();
    if ($summary.hasClass('is-error')) { event.preventDefault(); }
  });
})(jQuery);
