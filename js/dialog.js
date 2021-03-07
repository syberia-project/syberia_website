const animateCSS = (selector, animations, prefix = 'animate__') =>
  // We create a Promise and return it
  new Promise((resolve, reject) => {
    const animationNames = animations.map(item => `${prefix}${item}`);
    const nodes = $(selector);

    nodes.addClass(`${prefix}animated`);
    animationNames.forEach(animation => nodes.addClass(animation))

    // When the animation ends, we clean the classes and resolve the Promise
    function handleAnimationEnd(event) {
      event.stopPropagation();
      nodes.removeClass(`${prefix}animated`);
      animationNames.forEach(animation => nodes.removeClass(animation))
      resolve('Animation ended');
    }

    nodes.one('animationend', handleAnimationEnd);
  });

let currentModals = [];

const modals = $('.modal')
modals.on('show.bs.modal', function (e) {
  currentModals.push(e.target.id)

  const modal = $("#" + e.target.id)
  modal.addClass("show")
  modal.css("display", "block");
  animateCSS("#" + e.target.id + "> .modal-dialog > .modal-content", ["zoomIn", "faster"])
})
modals.on('hide.bs.modal', function (e) {
  if (currentModals.includes(e.target.id)) {
    animateCSS("#" + e.target.id + "> .modal-dialog > .modal-content", ["zoomOut", "faster"]).then(r => {
      const modal = $("#" + e.target.id)
      currentModals = currentModals.filter(item => item !== e.target.id)
      modal.modal("hide")
    })
    e.preventDefault()
  }
})
