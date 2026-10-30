function filter(word,arrays)
{
    const filteredarray = arrays.filter(array=>{
        if (word=='') {
           
            return word;
        }
        else{
        return array.match(word.toLowerCase())
    }
    })
    return filteredarray;
}