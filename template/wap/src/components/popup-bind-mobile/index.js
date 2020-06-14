import Vue from 'vue';
import BindMobile from './popup-bind-mobile.vue'

const constructor = Vue.extend(BindMobile);

const instance = new constructor({
  el: document.createElement('div')
});

constructor.prototype.open = function () {
  instance.show = true
  document.body.appendChild(instance.$el);
};

constructor.prototype.close = function () {
  const el = instance.$el;
  instance.show = false
  setTimeout(() => {
    el.parentNode && el.parentNode.removeChild(el);
  }, 100);
};

export default {
  open: instance.open,
  close: instance.close
};
