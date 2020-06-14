<template>
  <div>
    <component
      :is="'tpl_' + item.id"
      v-for="(item, index) in items"
      :key="index"
      :item="item"
      :type="type"
      v-if="fixedId.indexOf(item.id) == -1"
      @event="event"
    />
  </div>
</template>

<script>
import { all } from "@/pages/template";
export default {
  data() {
    return {};
  },
  props: {
    items: [Object, Array],
    /**
     * 页面类型
     * 1 ==> 商城首页
     * 2 ==> 店铺首页 需要店铺id
     * 3 ==> 商品详情 需要店铺id
     * 4 ==> 会员中心
     * 5 ==> 分销中心
     * 6 ==> 自定义页面 需要页面id
     * 9 ==> 积分商城
     */
    type: {
      type: [String, Number],
      required: true
    }
  },
  computed: {
    fixedId() {
      let id = [];
      if (this.type == 3) {
        id.push("detail_fixed");
      } else if (this.type == 5) {
        id.push("commission_fixed");
      }
      return id;
    }
  },
  methods: {
    event(event, item) {
      this.$emit("event", event, item);
    }
  },
  components: {
    ...all
  }
};
</script>
