<template>
  <div :class="item.id" :style="viewStyle" v-if="isShow">
    <van-cell-group :border="false">
      <CellAssembleGroup
        v-if="item.params.promoteType=='group'"
        :info="item.params||{}"
        :title-color="item.style.titlecolor"
        @confirm="groupConfirm"
        @callback="initData"
      />
      <CellPresellGroup
        v-if="item.params.promoteType=='presell'"
        :info="item.params||{}"
        :title-color="item.style.titlecolor"
      />
      <component
        :is="'cell-'+child.key+'-group'"
        v-for="(child,index) in item.data"
        :key="index"
        :items="child"
        :title-color="item.style.titlecolor"
      />
    </van-cell-group>
  </div>
</template>

<script>
import CellFullcutGroup from "../goods/component/detail/CellFullcutGroup";
import CellCouponGroup from "../goods/component/detail/CellCouponGroup";
import CellRebateGroup from "../goods/component/detail/CellRebateGroup";
import CellPresellGroup from "../goods/component/detail/CellPresellGroup";
import CellAssembleGroup from "../goods/component/detail/CellAssembleGroup";
export default {
  name: "tpl_detail_promote",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    },
    isShow() {
      let show = true;
      let arr = [];
      // 非活动情况下，判断是否有数据，促销优惠返利等信息都没数据情况下，不显示该组件
      if (!this.item.params.promoteType) {
        for (const key in this.item.data) {
          arr.push(this.item.data[key].show);
        }
        show = !arr.every(e => !e);
      }
      return show;
    }
  },
  methods: {
    groupConfirm(data) {
      this.$emit("event", "group", data);
    },
    initData() {
      this.$emit("event", "initData");
    }
  },
  components: {
    CellFullcutGroup,
    CellCouponGroup,
    CellRebateGroup,
    CellPresellGroup,
    CellAssembleGroup
  }
};
</script>

<style scoped>
</style>
