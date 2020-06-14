import Vue from 'vue';
import VanNotify from './Notify.vue';

const defaultOptions = {
  value: true,
  message: '',
  color: '#fff',
  background: '#1989fa',
  duration: 3000,
  className: '',
  onClick: null
};

const parseOptions = message => typeof message === 'string' ? { message } : message;

let timer = null;
let instance = null;

function Notify(options) {

  if (!instance) {
    instance = new (Vue.extend(VanNotify))({
      el: document.createElement('div')
    });
    document.body.appendChild(instance.$el);
  }

  options = {
    ...Notify.currentOptions,
    ...parseOptions(options)
  };

  Object.assign(instance, options);
  clearTimeout(timer);

  if (options.duration && options.duration > 0) {
    timer = setTimeout(Notify.clear, options.duration);
  }

  return instance;
}

Notify.clear = () => {
  if (instance) {
    instance.value = false;
  }
};

Notify.currentOptions = defaultOptions;

Notify.install = () => {
  Vue.use(VanNotify);
};

Vue.prototype.$Notify = Notify;

export default Notify
