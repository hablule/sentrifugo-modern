function validate(elements, validators) {
  var valid = true;
  elements = Array.from(elements);
  console.log("array"+elements.length)
  for (i = 0; i < elements.length; i++) {
    if (validators[elements[i]]) {
      for (j = 0; j < Object.keys(validators[elements[i]]).length; j++) {
        var result = isValid(elements[i], validators[elements[i]][j]);
        if (!result["result"]) {
          valid = false;
          document.getElementById(elements[i] + "_error").innerHTML =
            result["error"];
        }
      }
    }
  }
  return valid;
}

function isValid(element, validatorType) {
  var valid = true;
  var result = [];
  result["result"] = valid;
  if (validatorType["validate"] == "NotEmpty") {
    if (
      document.getElementById(element).value == null ||
      document.getElementById(element).value == ""||
      document.getElementById(element).value == "0"
    ) {
      valid = false;
      result["result"] = valid;
      result["error"] = validatorType["description"];
    }
  } else if (validatorType["validate"] == "EmailAddress") {
    var reg =
      /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/;
    if (
      document.getElementById(element).value == null ||
      document.getElementById(element).value == ""
    ) {
        valid = false;
        result["result"] = valid;
        result["error"] = "Field can't be empty!";
    } else {
      if (!(reg.test(document.getElementById(element).value))){
        valid = false;
        result["result"] = valid;
        result["error"] = validatorType["description"];
      } 
    }
  }
  return result;
}
