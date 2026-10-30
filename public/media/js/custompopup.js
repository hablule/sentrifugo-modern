function displayPopup(id = "popup-model") {
  document.getElementById(id).style.display = "block";
}

function removePopup(id = "popup-model") {
  document.getElementById(id).style.display = "none";
}
function displayNestedPopup(url, menuname, deep = false) {
  var containerDiv = document.createElement("div");
  var subContainerDiv = document.createElement("div");
  var titleDiv = document.createElement("div");
  var contentDiv = document.createElement("div");
  var iframe = document.createElement("iframe");
  var titleSpan = document.createElement("span");
  var closeSpan = document.createElement("span");
  iframe.setAttribute('id','nested')
  containerDiv.setAttribute("class", "nested-popup-model");
  containerDiv.id = "nested-popup-model";
  subContainerDiv.setAttribute("class", "nested-popup-model-content");
  titleDiv.setAttribute("class", "nested-popup-title");
  contentDiv.setAttribute("class", "nested-popup-main-content");
  closeSpan.setAttribute(
    "class",
    "ui-button-icon-primary ui-icon ui-icon-closethick"
  );
  closeSpan.style.float = "right";
  closeSpan.setAttribute("onclick", "removeNestedPopup("+deep+")");
 
  titleSpan.innerHTML = menuname;
  console.log(url);
  iframe.setAttribute("src", url);

  contentDiv.appendChild(iframe);
  titleDiv.appendChild(titleSpan);
  titleDiv.appendChild(closeSpan);
  subContainerDiv.appendChild(titleDiv);
  subContainerDiv.appendChild(contentDiv);
  containerDiv.appendChild(subContainerDiv);
  document.getElementsByTagName("body")[0].appendChild(containerDiv);
  
  if (deep) {
    window.parent.document.getElementsByClassName(
      "nested-popup-title"
    )[0].style.display = "none";
    window.parent.document.getElementsByClassName(
      "nested-popup-main-content"
    )[0].style.padding = "0px 0px 0px 0px";

    var deep_div = document.createElement('div');
    deep_div.setAttribute('id','deep_div');
    contentDiv.appendChild(deep_div);
  } else {
    window.parent.document.getElementsByClassName(
      "ui-dialog-titlebar"
    )[0].style.display = "none";

    var nested_div = document.createElement('div');
    nested_div.setAttribute('id', 'nested_div');
    contentDiv.appendChild(nested_div);
  }

  document.getElementById("nested-popup-model").style.display = "block";
  console.log($('#nested').contents().find('html').find('body').find('form'));
}

function removeNestedPopup(deep = false) {
  console.log("kk");
  var popup = document.getElementById("nested-popup-model");
  if (deep) {
    window.parent.document.getElementsByClassName(
      "nested-popup-title"
    )[0].style.display = "block";
    window.parent.document.getElementsByClassName(
      "nested-popup-main-content"
    )[0].style.padding = "20px 20px 20px 20px";
  } else {
    window.parent.document.getElementsByClassName(
      "ui-dialog-titlebar"
    )[0].style.display = "block";
  }
  popup.remove();
}
function removeNestedPopup_OnFinish(
  addpopupdata,
  controllername,
  con,
  textstr,
  newId,
  deep = false
) {
  console.log('here')
  var popup = window.parent.document.getElementById("nested-popup-model");

  if (deep) {
    window.parent.document.getElementsByClassName(
      "nested-popup-title"
    )[0].style.display = "block";
    window.parent.document.getElementsByClassName(
      "nested-popup-main-content"
    )[0].style.padding = "20px 20px 20px 20px";
  } else {
    window.parent.window.parent.document.getElementsByClassName(
      "ui-dialog-titlebar"
    )[0].style.display = "block";
  }

  var option = "";
  if (newId == "yes") {
    console.log("yess");
    console.log(con);

    var y = window.parent.$("#" + con);
    y.val(addpopupdata);
  }
  if (textstr != "") {
    var defOption = "<option value=''>Select " + textstr + "</option>";
    window.parent
      .$("#s2id_" + con + " .select2-choice span")
      .html("Select " + textstr);
  } else {
    if (con != "bg_checktype") {
      var defOption = "<option value=''> </option>";
      window.parent.$("#s2id_" + con + " .select2-choice span").html("");
    }
  }

  window.parent
    .$("#" + con)
    .find("option")
    .remove();
  window.parent
    .$("#" + con)
    .parent()
    .find(".select2-container")
    .find(".select2-search-choice")
    .remove();
  window.parent.$("#" + con).html(defOption + addpopupdata);

  popup.remove();
}
window.onclick = function (event) {
  if (event.target == document.getElementById("popup-model")) {
    document.getElementById("popup-model").style.display = "none";
  }
};
