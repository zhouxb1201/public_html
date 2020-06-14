<template>
  <div class="vs-steps">
    <div class="vs-steps-items">
      <div class="vs-item" v-for="(item,index) in items" :key="index" :style="styles">
        <div class="vs-step-container">
          <i class="vs-step-circle" :class="index <= is_current ? 'on' : ''"></i>
        </div>
        <div class="vs-step-line" :class="index <= is_current ? 'on' : ''"></div>

        <div class="vs-title">
          <p>{{item.level_name}}</p>
          <p>{{item.growth_num}}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  computed: {
    styles() {
      if (this.items.length > 4) {
        return { flexShrink: "0", width: "80px" };
      } else {
        return { flex: "auto" };
      }
    },
    is_current() {
      let index = 0;
      for (let i = 0; i < this.items.length; i++) {
        if (this.items[i].is_current_member_level == 1) {
          index = i;
          break;
        }
      }
      return index;
    }
  },
  mounted() {
    if (this.items.length > 3) {
      document.querySelector(".vs-steps").scrollLeft = parseInt(
        this.is_current * 80
      );
    }
  },
  props: {
    items: [Array]
  }
};
</script>

<style scoped>
.vs-steps {
  overflow-y: hidden;
  overflow-x: auto;
  margin: 0 10px;
}
.vs-steps-items {
  display: flex;
  margin: 0 0 10px;
  position: relative;
}
.vs-item {
  font-size: 14px;
  position: relative;
  color: #ffffff;
  float: left;
  overflow: hidden;
}
.vs-step-container {
  position: absolute;
  top: 8px;
  left: 8px;
  padding: 0 8px;
  z-index: 1;
}
.vs-step-circle {
  display: block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background-color: #ebedf0;
}
.vs-step-circle.on {
  background-color: #ffe678;
  box-shadow: 0px 0px 10px 2px rgba(255, 230, 120, 1);
}
.vs-step-line {
  position: absolute;
  left: 0;
  top: 10px;
  width: 100%;
  height: 1px;
  background-color: rgba(235, 237, 240, 0.5);
}
.vs-step-line.on {
  background-color: #ffe678;
}
.vs-title {
  font-size: 12px;
  padding-top: 20px;
  display: block;
  color: #ffffff;
  line-height: 16px;
  padding-left: 10px;
}
.vs-title p {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>