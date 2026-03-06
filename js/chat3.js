const hamburger = document.querySelector(".toggle-btn-O");
const toggler = document.querySelector("#icon");
hamburger.addEventListener("click",function(){
    document.querySelector("#sidebar").classList.toggle("expand");
    toggler.classList.toggle("bx bx-edit");
    toggler.classList.toggle("bxs-chevrons-left");
});