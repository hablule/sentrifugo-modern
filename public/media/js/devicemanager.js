const URL = "https://localhost:44362/api/"
$(document).ready(function () {
    scandevice();
});
function scandevice() {
    $.ajax({
        url: URL + "ConnectDevice/scandevice",
        type: "GET",
        dataType: "json",
        success: (data) => {
            console.log(data);
            if (data.length > 0) {
                var table = document.getElementById("tabledevice");
                for (var i = 0; i < data.length; i++) {
                    var row = table.insertRow(i + 1);
                    var cell1 = row.insertCell(0);
                    cell1.innerHTML = data[i].deviceID;
                    var cell2 = row.insertCell(1);
                    cell2.innerHTML = data[i].deviceIP;
                    var cell3 = row.insertCell(2);
                    cell3.innerHTML = data[i].deviceName;
                    var cell4 = row.insertCell(3);
                    cell4.innerHTML = data[i].devicePort;
                    var cell5 = row.insertCell(4);
                    cell5.innerHTML = data[i].connectionMode;
                    var cell6 = row.insertCell(5);
                    var btn = document.createElement("BUTTON");
                    btn.id = data[i].deviceID;
                    btn.setAttribute("onclick", `addDevice(${JSON.stringify(data[i])})`);
                    btn.innerHTML = "ADD DEVICE";
                    btn.className = "btn btn-primary";
                    cell6.innerHTML = btn.outerHTML;
                }
            }
            else {
                table = document.getElementById("tabledevice");
                row = table.insertRow(1);
                cell1 = row.insertCell(0);
                cell1.innerHTML = "No Device";
            }
        },
        error: function (data) {
            console.log(data);
        }
    });
}

function addDevice(device){
    console.log(device)
    $.ajax({
        url: URL + "ConnectDevice/adddevice",
        type: "POST",
        dataType: "json",
        contentType: "application/json",
        data: JSON.stringify(device),
        success: () => {
            window.location.reload();
        },
        error: (error) => {
            console.log(error)
            alert(`error has occurred ${error}`)
        }
    });
}
