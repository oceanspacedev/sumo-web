const $ = window.jQuery;
const Scanner = window.Html5QrcodeScanner;

if ($ && Scanner && document.getElementById('reader')) {
    const onScanSuccess = (decodedText) => {
        $('#result').val(decodedText);
        $('#loading').show();

        $.ajax({
            url: '/search-product',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: { id: decodedText },
            success(response) {
                if (response.length > 0) {
                    const data = response[0];
                    $('#product').val(data.product);
                    $('#category').val(data.category.category);
                    $('#unittype').val(data.unit_type.unit_type);
                    $('#price').val(data.price);
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
