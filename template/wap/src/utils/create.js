/**
 *  使用常用选项创建组件
 */
import { share } from '@/mixins';

const install = function (Vue) {
  Vue.component(this.name, this);
};

export default function (sfc) {
  sfc.install = sfc.install || install;
  sfc.mixins = sfc.mixins || [];
  sfc.mixins.push(share);
  sfc.methods = sfc.methods || {};
  sfc.components = Object.assign(sfc.components || {}, {});
  return sfc;
};

