<template>
  <div class="level-card-head-wrap">
    <div class="head">
      <div class="user-info">
        <div class="avatar-wrap">
          <div class="avatar">
            <img :src="img" :onerror="$ERRORPIC.noAvatar" />
          </div>
        </div>
        <div class="personal-wrap">
          <div class="name-wrap">
            <span class="header-name">{{name}}</span>
            <span class="header-edit">{{growth_num}}</span>
          </div>
          <div class="plus-wrap">{{text}}</div>
        </div>
      </div>
      <slot name="bottom"></slot>
    </div>
  </div>
</template>

<script>
export default {
  data() {
    return {};
  },
  props: {
    img: [String],
    name: [String],
    growth_num: [String, Number],
    text: [String]
  },
  computed: {},
  methods: {}
};
</script>

<style scoped>
.level-card-head-wrap {
  overflow: hidden;
  position: relative;
  background-color: #f8f8f8;
}
.head {
  position: relative;
  overflow: hidden;
  height: 80px;
  background: linear-gradient(90deg, #eb3c3c, #ff7459);
  margin: 10px;
  border-radius: 10px;
  -webkit-box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  box-shadow: 0 0 10px 0 rgba(0, 0, 0, 0.05);
  color: #fff;
  padding-top: 10px;
}
.user-info {
  padding: 0 15px 10px;
  display: flex;
  align-items: center;
  position: relative;
}
.user-info .avatar-wrap {
  position: relative;
  flex-shrink: 0;
}
.user-info .avatar-wrap .avatar {
  width: 50px;
  height: 50px;
  overflow: hidden;
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.15);
  border: 1px solid rgba(255, 255, 255, 0.4);
  border-radius: 60px;
}
.user-info .avatar-wrap .avatar img {
  display: block;
  width: 100%;
  height: 100%;
}
.personal-wrap {
  flex: 1;
  overflow: hidden;
  padding: 0 0 0 15px;
  position: relative;
}
.name-wrap {
  font-size: 12px;
  color: #fff;
  display: flex;
  align-items: center;
  line-height: 24px;
}
.name-wrap .header-name {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.name-wrap .header-edit {
  flex-shrink: 0;
  margin: 0 0 0 10px;
  vertical-align: middle;
}
.plus-wrap {
  font-size: 14px;
  color: #ffe678;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
</style>
