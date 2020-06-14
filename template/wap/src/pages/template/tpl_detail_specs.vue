<template>
  <div :class="item.id" :style="viewStyle" v-if="isShow">
    <van-cell is-link @click="click">
      <div slot="icon" class="title" :style="{color:item.style.titlecolor}">规格</div>
      <div class="value" :style="{color:valueColor}">{{valueText}}</div>
    </van-cell>
    <div></div>
  </div>
</template>

<script>
export default {
  name: "tpl_detail_specs",
  data() {
    return {};
  },
  props: {
    type: [String, Number],
    item: Object
  },
  computed: {
    isShow() {
      return this.item.params.show;
    },
    viewStyle() {
      return {
        marginTop: this.item.style.margintop + "px",
        marginBottom: this.item.style.marginbottom + "px"
      };
    },
    valueText() {
      return this.item.params.valueText || "请选择规格";
    },
    valueColor() {
      return this.item.params.isCurrent
        ? this.item.style.currentcolor
        : this.item.style.nocurrentcolor;
    }
  },
  mounted() {},
  methods: {
    click() {
      this.$emit("event", "specs", this.item);
    }
  },
  components: {}
};
</script>

<style scoped>
.title {
  width: 50px;
  color: #606266;
}

.value {
  color: #909399;
}
</style>
