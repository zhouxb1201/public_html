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
      <div class="sup-time">
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
}

.logo {
  width: 13.334%;
  margin-left: 20px;
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
  margin-left: 10px;
}

.info > div {
  display: flex;
  align-items: center;
  font-size: 12px;
  line-height: 1;
  margin: 5px 0;
  height: 22px;
}

.info > div.sup-time {
  align-items: flex-start;
  flex-direction: column;
}

.info .name {
  color: #c5a570;
  max-width: 100px;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 14px;
}

.info .btn-icon {
  max-width: 16px;
}

.info .tag {
  background: transparent;
  color: #c5a570;
  margin-left: 8px;
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 120px;
  padding: 2px 4px;
  line-height: 1;
  height: 16px;
  display: block;
  border: 0;
}

.info .sup {
  margin-right: 8px;
}

.info .sup span {
  display: inline-block;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  max-width: 90px;
  vertical-align: middle;
  line-height: 1.2;
}
</style>
