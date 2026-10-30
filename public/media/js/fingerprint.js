async function scanFingerPrint(deviceAPIUrl,fingerIndex, index, empID) {
    //e.preventDefault();
    let num_of_finger = $('#num_of_fingers').val();
    let deviceId = $('#device_to_scan').val();
    let empId = empID;
    let scanedSuccess = false;
    console.log(num_of_finger);
    device_api_url = deviceAPIUrl;
    $(`#${index}`).find('img').show();
    $.ajax({
        url: device_api_url + "ConnectDevice/scanfingerprint?deviceId=" + deviceId + "&num_of_fingers=1" + "&empId=" + empId,
        type: "POST",
        success: function(response) {
            if (templateData[fingerIndex] == undefined) {
                templateData[fingerIndex] = {};
            }
            if (index >= 5) {
                templateData[fingerIndex]['left'] = response.data;
            } else {
                templateData[fingerIndex]['right'] = response.data;
            }

            $(`#${index}`).find('.addrecord').hide();
            $('#num_of_fingers').val(++num_of_finger);
           
            $(`#${index}`).append('<span class="sprite delete"></span>');
            $(`#${index}`).find('.delete').click((e) => {
                if (index >= 5) {
                    delete templateData[fingerIndex]['left'];
                } else {
                    delete templateData[fingerIndex]['right'];
                }
                $(`[name='${fingerIndex}']`).val(JSON.stringify(templateData[fingerIndex]));
                $(`#num_of_fingers`).val(--num_of_finger);
                //templateData[fingerIndex].splice(index, 1);
                $(`#${index}`).find('.addrecord').show();
                $(`#${index}`).find('.delete').remove();
            });

            console.log((templateData[fingerIndex]));
            $(`[name='${fingerIndex}']`).show();
            console.log(`[name='${fingerIndex}']`);
            $(`#${index}`).find('img').hide();
            $(`[name='${fingerIndex}']`).val(JSON.stringify(templateData[fingerIndex]));
            alert('Fingerprint scanned successfully');
        },
        error: function() {
            scanedSuccess = false;
            $(`#${index}`).find('img').hide();
            alert('Fingerprint scan failed. Please try again');
        }

    });

    return scanedSuccess;

}

function deleteFingerPrint(fingerIndex, hand, templateData, index) {
    delete templateData[fingerIndex][hand];
    $("#num_of_fingers").val(Object.keys(templateData[fingerIndex]).length);
    $(`[name='${fingerIndex}']`).val(JSON.stringify(templateData[fingerIndex]));
    $(`#${index}`).find('.addrecord').show();
    $(`#${index}`).find('.delete').remove();

}
