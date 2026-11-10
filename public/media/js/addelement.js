function displayPopup(){
    document.getElementById('popup-model').style.display = 'block';
}

function removePopup(){
    document.getElementById('popup-model').style.display = 'none';
}
window.onclick = function(event){
    if(event.target == document.getElementById('popup-model')){
        document.getElementById('popup-model').style.display = 'none';
    }
}