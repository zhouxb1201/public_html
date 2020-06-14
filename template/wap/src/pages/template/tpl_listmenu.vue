<template>
  <div :class="item.id">
    <div class="van-cell-group card-group-box van-hairline--top-bottom" :style="itemStyle">
      <div
        class="van-cell van-cell--clickable"
        v-for="(child,index) in item.data"
        :key="index"
        :style="background"
        @click="toLink(child.linkurl)"
      >
        <i
          class="van-icon van-cell__left-icon"
          v-if="child.iconclass"
          :style="iconcolor"
          :class="'van-icon-'+child.iconclass"
        ></i>
        <div class="van-cell__title">
          <span :style="textcolor">{{child.text}}</span>
        </div>
        <div class="van-cell__value fs-12 text-secondary">
          <span v-if="child.remark" :style="remarkcolor">{{child.remark}}</span>
        </div>
        <i class="van-icon van-icon-arrow van-cell__right-icon"></i>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: "tpl_listmenu",
  data() {
    return {
      itemStyle: {
        marginTop: this.item.style.margintop + "px"
      },
      background: {
        background: this.item.style.background
      },
      textcolor: {
        color: this.item.style.textcolor
      },
      iconcolor: {
        color: this.item.style.iconcolor
      },
      remarkcolor: {
        color: this.item.style.remarkcolor
      }
    };
  },
  props: {
    type: [String, Number],
    item: Object
  },
  methods: {
    toLink(link) {
      this.$router.push(link);
    }
  }
};
</script>

<style scoped>
.van-icon {
  line-height: 24px;
}

.van-cell--clickable:active {
  background-color: #e8e8e8 !important;
}
.van-cell-group {
  border-radius: 10px;
  margin: 0 10px;
  overflow: hidden;
}
</style>
