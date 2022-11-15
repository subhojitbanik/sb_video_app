function myFunction() {
    var copyText = document.getElementById("myInput");
    copyText.select();
    copyText.setSelectionRange(0, 99999);
    navigator.clipboard.writeText(copyText.value);

    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "Copied: " + copyText.value;
}

function outFunc() {
    var tooltip = document.getElementById("myTooltip");
    tooltip.innerHTML = "Copy to clipboard";
}

let idleTimer = null;
let idleState = false;

function showFoo(time) {
  clearTimeout(idleTimer);
  if (idleState == true) {
    $("#icon-blur").removeClass("inactive");
  }
  idleState = false;
  idleTimer = setTimeout(function() {
    $("#icon-blur").addClass("inactive");
    idleState = true;
  }, time);
}

showFoo(2000);

$(window).mousemove(function(){
    showFoo(2000);
});