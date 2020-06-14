<template>
  <div class="head" :style="headStyle">
    <div class="info">
      <div class="img">
        <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
      </div>
      <div class="name">{{info.member_name}}</div>
    </div>
    <van-row class="head-foot" type="flex" justify="space-around">
      <div class="item" v-for="(item,index) in agentInfo" :key="index">
        <div
          :class="agentInfo.length == 1 ? 'flex-auto-center' : (index == 0?'':'van-hairline--left')"
        >
          <div class="name">{{item.name}}</div>
          <span @click="clickLevel(item)">{{item.applyState == 2 ? item.level_name : '- -'}}</span>
        </div>
      </div>
    </van-row>
  </div>
</template>
<script>
export default {
  props: {
    info: Object,
    agentInfo: Array
  },
  computed: {
    headStyle() {
      return {
        background:
          "url(" +
          this.$BASEIMGPATH +
          "bonus-bg.png" +
          ") no-repeat top center",
        backgroundSize: "cover"
      };
    }
  },
  methods: {
    clickLevel(item) {
      if (item.applyState == 2) {
        this.$router.push(item.levelLink);
      }
    }
  }
};
</script>
<style scoped>
.head {
  position: relative;
  overflow: hidden;
  width: 100%;
  height: 170px;
}

.head .info {
  display: flex;
  align-items: center;
  justify-content: center;
  flex-flow: column;
  z-index: 1;
  height: 120px;
}

.head .info .name {
  color: #ffffff;
  margin-top: 10px;
}

.head .info .img {
  width: 60px;
  height: 60px;
  overflow: hidden;
  border-radius: 50%;
  border: 2px solid #ffffff;
}

.head .info .img img {
  width: 100%;
  height: 100%;
}

.head .head-foot {
  position: absolute;
  bottom: 0;
  width: 100%;
  height: 50px;
  background: rgba(0, 0, 0, 0.15);
  display: flex;
  align-items: center;
  text-align: center;
  color: #ffffff;
  font-size: 12px;
}

.head .head-foot .item {
  flex: 1;
  line-height: 1.6;
}

.head .head-foot .item span {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  display: block;
  width: 90%;
  margin: 0 auto;
  flex: 1;
}

.head .head-foot .item .name {
  padding: 0 10px;
  white-space: nowrap;
  flex: 1;
}
</style>

