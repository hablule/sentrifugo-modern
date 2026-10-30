classes = JSON.parse(classes);

$("#classfun").on("change", function () {
  getParams();
});

function getParams(){
  var selectedValue = document.getElementById("classfun").value;
  if (selectedValue == "empty") {
    document.getElementById("total-form-controller").innerHTML = "";
  } else {
    var selectedValue_array = selectedValue.split(".");
    var className = selectedValue_array[0];
    var methodName = selectedValue_array[1];

    var no_parameters = classes[className]["methods"][methodName]["no_args"];
    var classPath = classes[className]["path"];
    document.getElementById("classPath").value = classPath;
    var total_div = document.getElementById("total-form-controller");

    var res = $.ajax({
      url: base_url + "/userdefinedclass/createfields",
      type: "POST",
      data: {
        field_size: no_parameters,
      },
      dataType: "json",
      async: false,
    }).responseText;
    total_div.innerHTML = "";
    for (var i = 0; i < no_parameters; i++) {
      var holder_div = document.createElement("div");
      holder_div.setAttribute("id", "holder_div_" + i);
      var division_div = document.createElement("div");
      var inner_div = document.createElement("div");
      var selected_text = document.createElement("input");
      var input_text = document.createElement("input");
      var search_div = document.createElement("div");
      var label = document.createElement("label");
      var error = document.createElement("div");
      error.setAttribute("id", "error_" + i);
      error.style.color = "red";
      search_div.setAttribute("class", "search_div");
      search_div.setAttribute("id", "search_div_" + i);
      search_div.style.display = "none";
      var ul = document.createElement("ul");
      ul.setAttribute("id", "ul_" + i);

      holder_div.setAttribute("class", "new-form-ui holder_div");
      division_div.setAttribute("class", "division");
      input_text.style.width = "175px";
      input_text.setAttribute("type", "text");

      input_text.setAttribute("id", "input_text_" + i);
      selected_text.setAttribute("type", "text");
      selected_text.setAttribute("readonly", true);

      selected_text.setAttribute("id", "selected_text_" + i);
      label.innerHTML =
        classes[className]["methods"][methodName]["arguments"][i]["name"];
      var fieldType =
        classes[className]["methods"][methodName]["arguments"][i]["type"];

      var res = $.ajax({
        url: base_url + "/userdefinedclass/getfields",
        type: "POST",
        data: {
          fieldType: fieldType.replace("<required>", ""),
        },
        dataType: "json",
        async: false,
      }).responseText;
      res = JSON.parse(res);

      res.forEach(function (element) {
        var li = document.createElement("li");

        li.innerHTML = element["fieldName"];
        ul.appendChild(li);
      });

      search_div.appendChild(input_text);
      search_div.appendChild(ul);

      inner_div.appendChild(label);
      inner_div.appendChild(selected_text);
      inner_div.appendChild(document.createElement("br"));
      inner_div.appendChild(search_div);
      inner_div.appendChild(error);
      division_div.appendChild(inner_div);
      holder_div.appendChild(division_div);
      total_div.appendChild(holder_div);

      $("#input_text_" + i).on("keyup", function () {
        var filterValue = document.getElementById(this.id).value.toUpperCase();
        var id_index = this.id.at(-1);

        var li = document
          .getElementById("ul_" + id_index)
          .getElementsByTagName("li");
        for (var i = 0; i < li.length; i++) {
          if (li[i].innerHTML.toUpperCase().indexOf(filterValue) != -1) {
            li[i].style.display = "";
          } else {
            li[i].style.display = "none";
          }
        }
      });
      $("#ul_" + i + " > li").on("click", function () {
        var id_index = $(this).parent()[0].id.at(-1);
        this.setAttribute("class", "selected");
        document.getElementById("selected_text_" + id_index).value =
          this.innerHTML;
      });
      $("#selected_text_" + i).on("click", function () {
        var id_index = this.id.at(-1);
        document.getElementById("search_div_" + id_index).style.display =
          "block";
      });
      $("#input_text_" + i).on("click", function () {
        var id_index = this.id.at(-1);
        document.getElementById("search_div_" + id_index).style.display =
          "block";
      });
    }

    $(document).mouseup(function () {
      var searchDivs = document.getElementsByClassName("search_div");
      for (var i = 0; i < searchDivs.length; i++) {
        searchDivs[i].style.display = "none";
      }
    });
  }
}
