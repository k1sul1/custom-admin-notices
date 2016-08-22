function toArray(arrayLike) {
    return Array.prototype.slice.call(arrayLike);
}

function clearSiblingInputs(evt) {
    var target = evt.currentTarget || evt.target;

    if (!target) {
        return;
    }

    evt.preventDefault();

    toArray(target.parentNode.querySelectorAll("input")).
    forEach(function (input) {
        input.checked = false;
    });
}

toArray(document.querySelectorAll("[data-action=clear]")).
forEach(function (el) {
    el.addEventListener("click", clearSiblingInputs);
    el.classList.remove("cloak");
});
