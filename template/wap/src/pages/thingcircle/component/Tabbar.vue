<template>
  <div class="footer">
    <nav class="footer-tabbar" :style="isBottom">
      <div class="item" v-for="(item,index) in items" :key="index" @click="switchTo(index)">
        <span :class="{'plus': index == 1,'active': isActive == index}">{{item.title}}</span>
      </div>
    </nav>
  </div>
</template>
<script>
export default {
  data() {
    return {
      isActive: 0,
      items: [
        {
          title: "首页",
        },
        {
          title: ""
        },
        {
          title: "我的"
        }
      ]
    };
  },
  computed: {
    isBottom() {
      if (this.$store.state.tabbar.isShowTabbar)
        return {
          bottom: 50 + "px"
        };
    },
  },
  mounted(){
    if(this.$route.path == "/thingcircle/mine"){
      this.isActive = 2;
    }else if(this.$route.path == "/thingcircle/home"){
      this.isActive = 0;
    }
  },
  methods: {
    switchTo(index) {
      let path;
      if (index == 0) {
        path = "/thingcircle/home";
      } else if (index == 1) {
        path = "/thingcircle/release";
      } else if (index == 2) {
        path = "/thingcircle/mine";
      }
      if (index !== 1) {
        this.isActive = index;
      }
      this.$router.replace(path);
    }
  }
};
</script>

<style scoped>
.footer {
  height: 50px;
}
.footer-tabbar {
  left: 0;
  bottom: 0;
  position: fixed;
  z-index: 999;
  display: flex;
  width: 100%;
  height: 50px;
  background-color: #fff;
}
.footer-tabbar::before {
  content: "";
  position: absolute;
  pointer-events: none;
  -webkit-box-sizing: border-box;
  box-sizing: border-box;
  top: -1px;
  left: 0;
  width: 100%;
  -webkit-transform: scaleY(0.5);
  transform: scaleY(0.5);
  height: 1px;
  background-color: #ebedf0;
}
.footer-tabbar .item {
  flex: 1;
  display: -ms-flexbox;
  display: flex;
  color: #666;
  font-size: 12px;
  line-height: 1;
  -ms-flex-align: center;
  align-items: center;
  -ms-flex-direction: column;
  flex-direction: column;
  -ms-flex-pack: center;
  justify-content: center;
  font-size: 14px;
}
.footer-tabbar .item .plus {
  background: #f62832;
  border-radius: 6px;
  height: 28px;
  width: 28px;
  position: relative;
  display: block;
}
.footer-tabbar .item .plus:before {
  content: "";
  width: 14px;
  height: 2px;
  background-color: #fff;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
.footer-tabbar .item .plus:after {
  content: "";
  width: 2px;
  height: 14px;
  background-color: #fff;
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
}
.footer-tabbar .item .popup {
  position: absolute;
  top: -54px;
  left: 50%;
  transform: translateX(-50%);
}
.footer-tabbar .item .sup {
  background-color: rgba(0, 0, 0, 0.6);
  display: flex;
  font-size: 12px;
  color: #fff;
  border-radius: 10px;
}

.sup .wrap {
  text-align: center;
  flex: 1;
}
.pl-14 {
  padding: 8px 8px 8px 14px;
}
.pr-14 {
  padding: 8px 14px 8px 8px;
}
.sup .wrap .small-icon {
  width: 20px;
  height: 20px;
  background-color: red;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 50%;
  margin-bottom: 6px;
}
.corner {
  position: absolute;
  left: 50%;
  width: 0px;
  height: 0px;
  border: 8px solid rgba(0, 0, 0, 0.6);
  border-bottom-color: transparent;
  border-left-color: transparent;
  border-right-color: transparent;
  transform: translateX(-50%);
}
.active {
  font-weight: 700;
  color: #ff454e;
  font-size: 16px;
  transition: all 0.2s;
}
</style>