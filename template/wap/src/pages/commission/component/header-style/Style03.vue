<template>
  <div class="box">
    <div class="logo">
      <div class="img">
        <img :src="info.user_headimg | BASESRC" :onerror="$ERRORPIC.noAvatar" />
      </div>
    </div>
    <div class="info">
      <div>
        <div class="name">{{info.name}}</div>
        <van-button
          size="mini"
          round
          plain
          class="tag"
          to="/commission/level"
          v-if="info.level_name"
        >
          <van-icon name="v-icon-level" class="btn-icon" />
          {{info.level_name}}
        </van-button>
      </div>
    </div>
    <div class="foot">
      <div class="sup" v-if="info.referee_name">
        推荐人：
        <span>{{info.referee_name}}</span>
      </div>
      <div class="time">
        加入时间：
        <span>{{info.reg_time}}</span>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    info: Object
  }
};
</script>

<style scoped>
.box {
  display: flex;
  align-items: center;
  flex-direction: column;
  justify-content: center;
}

.logo {
  width: 13.334%;
}

.logo .img {
  border-radius: 50%;
  position: relative;
  height: 0;
  width: 100%;
  padding: 50% 0;
  overflow: hidden;
  background: #ffffff;
}

.logo .img img {
  width: 100%;
  height: 100%;
  position: absolute;
  top: 0;
  left: 0;
}

.info {
  color: #fff;
  margin-top: 10px;
  margin-bottom: 22px;
}

.info > div {
  display: flex;
  justify-content: center;
  align-items: center;
  font-size: 12px;
  line-height: 1;
  height: 22px;
}

.info .name {
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
}

.info .tag {
  background: #7085a5;
  color: #fff;
  margin-left: 8px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 100px;
  padding: 2px 4px;
  line-height: 1;
  height: 16px;
  display: block;
  border: 0;
}

.foot {
  position: absolute;
  bottom: 0;
  display: flex;
  width: 100%;
  font-size: 12px;
  text-align: center;
  align-items: center;
  height: 28px;
  background: rgba(0, 0, 0, 0.2);
  color: #fff;
}

.foot .sup {
  flex: 1;
  border-right: 1px solid #fff;
}
.foot .sup span {
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 90px;
  vertical-align: middle;
}
.foot .time {
  flex: 1;
}
</style>
