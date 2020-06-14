<template>
  <div class="box">
    <van-icon class="icon" :name="active.icon" :color="active.color" size="2.2em" />
    <span class="text" :style="'color:'+active.color">{{active.text}}</span>
  </div>
</template>

<script>
export default {
  data() {
    return {
      arr: [
        {
          state: 1,
          icon: "clock-o",
          color: "#1989fa",
          text: "进行中"
        },
        {
          state: 2,
          icon: "passed",
          color: "#06bf04",
          text: "已完成"
        },
        {
          state: 3,
          icon: "close",
          color: "#909399",
          text: "已失效"
        }
      ]
    };
  },
  props: {
    state: [Number, String]
  },
  computed: {
    active() {
      return this.arr.filter(({ state }) => state == this.state)[0];
    }
  }
};
</script>

<style scoped>
.box {
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 10px 0;
}
</style>
