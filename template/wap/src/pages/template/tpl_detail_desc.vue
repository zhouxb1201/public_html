<template>
  <div :class="item.id">
    <van-tabs v-model="active" class="tab-box">
      <van-tab v-for="(tab,t) in tabs" :title="tab.text" :key="t">
        <component :is="tab.name" :data="tab.data" />
      </van-tab>
    </van-tabs>
  </div>
</template>

<script>
import Descript from "../goods/component/detail/Descript";
import Attribute from "../goods/component/detail/Attribute";
import Catalog from "../goods/component/detail/Catalog";
import Evaluate from "../goods/component/detail/Evaluate";
export default {
  name: "tpl_detail_desc",
  data() {
    return {
      active: 0
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    tabs() {
      return this.item.params.tabs;
    }
  },
  components: {
    Descript,
    Attribute,
    Catalog,
    Evaluate
  }
};
</script>

<style scoped>
.tab-box {
  background: #fff;
}
</style>
