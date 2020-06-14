<template>
  <div :class="item.id">
    <div class="vui-icon-group card-group-box" :style="itemStyle" :class="itemClass">
      <router-link
        class="vui-icon-col e-handle"
        v-for="(child,index) in item.data"
        :key="index"
        tag="div"
        :to="child.linkurl"
      >
        <div class="icon">
          <img v-lazy="child.imgurl" :key="child.imgurl" pic-type="square" />
        </div>
        <div class="text" :style="{'color':child.color}">{{child.text}}</div>
        <div class="txt"><span class="num" v-if="child.num>=0">{{child.num}}</span><span v-if="child.unit">个</span></div>
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: "tpl_menu",
  data() {
    return {
      itemStyle: {
        background: this.item.style.background
      }
    };
  },
  computed: {
    itemClass() {
      return "col-" + this.item.style.rownum;
    }
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
.vui-icon-group {
  position: relative;
  overflow: hidden;
  background: #fff;
  border-radius: 10px;
  margin: 10px;
  padding-top: 5px;
}
.vui-icon-group .vui-icon-col {
  width: 25%;
  height: auto;
  position: relative;
  padding: 0;
  margin: 5px 0;
  text-align: center;
  transition: background-color 300ms;
  -webkit-transition: background-color 300ms;
  float: left;
  border: none !important;
  height: 76px;
}
.vui-icon-group.col-3 .vui-icon-col {
  width: 33.333333%;
}
.vui-icon-group.col-4 .vui-icon-col {
  width: 25%;
}
.vui-icon-group.col-5 .vui-icon-col {
  width: 20%;
}

.vui-icon-group .vui-icon-col .icon {
      height: 44px;
    margin: auto;
    text-align: center;
    line-height: 44px;
}
.vui-icon-group .vui-icon-col .icon img {
  height: 44px;
  width: 44px;
}
.vui-icon-group .vui-icon-col .text {
  text-align: center;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  padding: 4px 4px 4px;
  color: #666;
  font-size: 12px;
}
.vui-icon-group .vui-icon-col .txt{
  font-size: 12px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    padding: 0 5px;
}
.vui-icon-group .vui-icon-col .num{
  color: #ff4444;
    padding: 0 2px;
}
</style>
