function changeoptionfont() {
    var x = document.getElementsByClassName('select2-results');
    console.log(x)
    for (const iterator of x) {
        var child = iterator.firstChild;
        child.style.fontFamily = child.innerHTML;
        
    }
    console.log('jhjshj');

}

