const $ = window.jQuery;
const moment = window.moment;
const Scanner = window.Html5QrcodeScanner;

if ($ && moment && Scanner && document.getElementById('reader')) {
    const onScanSuccess = (decodedText) => {
        $('#result').val(decodedText);
        $('#loading').show();

        $.ajax({
            url: '/search',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: { id: decodedText },
            success(response) {
                if (response.length > 0) {
                    const data = response[0];
                    const requestDetail = data.request_detail.find((detail) => detail.id == decodedText);

                    if (requestDetail) {
                        $('#product').val(requestDetail.product.product);
                        $('#qty_approved').val(requestDetail.qty_approved);
                        $('#qty_remaining').val(requestDetail.qty_remaining);
                        $('#qty_request').val(requestDetail.qty_request);
                        $('#description').val(requestDetail.description);
                    }

                    const requestApproval = data.request_approval;
                    if (requestApproval) {
                        const executor = requestApproval.find((detail) => detail.approval_type === 'EXECUTOR');
                        $('#closed_by').val(executor?.user?.fullname ?? '');
                        $('#closed_at').val(executor?.approved_at ? moment(executor.approved_at).format('DD-MMM-YYYY HH:mm') : '');
                    }

                    $('#name').val(data.user.fullname);
                    $('#date').val(moment(data.date).format('DD-MMM-YYYY HH:mm'));
                    $('#status_po').val(data.status_po === 0 ? 'TIDAK' : 'YA');
                    $('#status_client').val(data.status_client === 0 ? 'MENUNGGU' : (data.status_client === 1 ? 'SELESAI' : 'DIBATALKAN'));
                    $('#notes').val(data.notes);
                    $('#user_notes').val(data.user_notes);
                }
                $('#loading').hide();
            },
            error(xhr, status, error) {
                console.error(xhr.status + ': ' + xhr.statusText);
                console.error(error);
                $('#loading').hide();
            },
        });
    };

    const onScanFailure = (error) => {
        console.warn(`Code scan error = ${error}`);
    };

    const scanner = new Scanner(
        'reader',
        { fps: 10, qrbox: { width: 250, height: 250 } },
        false
    );
    scanner.render(onScanSuccess, onScanFailure);
}
