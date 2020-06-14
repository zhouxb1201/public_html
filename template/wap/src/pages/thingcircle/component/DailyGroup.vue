<template>
  <div class="wrap">
    <div class="item" v-for="(item,index) in items" :key="index">
      <div class="date">
        <span>{{item.create_day | getformate(2)}}</span>
        <span class="month">{{item.create_day | getformate(1)}}月</span>
      </div>
      <div class="boxes">
        <div
          class="content"
          v-for="(child , cindex) in item.child_data"
          :key="cindex"
          @click="toDetail(child.id,child.thing_type)"
        >
          <img
            :src="child.img_temp_array.length > 0 ? child.img_temp_array[0].pic_cover : ''"
            :onerror="$ERRORPIC.noGoods"
            v-if="child.thing_type == 1"
          />
          <img :src="child.video_img.pic_cover" :onerror="$ERRORPIC.noGoods" v-else />
          <span>{{child.title ? child.title : child.content}}</span>
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
    items: [Object, Array]
  },
  filters: {
    getformate(timeStamp, index) {
      if (!timeStamp) return timeStamp;
      var time_list = timeStamp.split("-");
      return time_list[index];
    },
    isToday(date) {
      if (new Date(date).toDateString() == new Date().toDateString()) {
        return "今天";
      }
    }
  },
  methods: {
    toDetail(id, type) {
      if (type == 1) {
        this.$router.push({
          name: "thingcircle-grapdetail",
          params: {
            thingid: id
          }
        });
      } else {
        this.$router.push({
          name: "thingcircle-vediodetail",
          params: {
            thingid: id
          }
        });
      }
    }
  }
};
</script>

<style scoped>
.wrap {
  background-color: #ffffff;
}
.wrap .item {
  display: flex;
  padding: 10px 0px;
}
.wrap .item .date {
  font-size: 18px;
  width: 70px;
  text-align: center;
  font-weight: 700;
}
.wrap .item .date .month {
  font-size: 12px;
  font-weight: normal;
}
.wrap .item .boxes {
  flex: 1;
}
.wrap .item .boxes .content {
  display: flex;
  margin-top: 10px;
}
.wrap .item .boxes .content:first-child {
  margin-top: 0px;
}
.wrap .item .boxes .content img {
  width: 50px;
  height: 50px;
  display: inline-block;
}
.wrap .item .boxes .content span {
  display: inline-block;
  flex: 1;
  padding: 0px 20px 0px 10px;
  max-height: 40px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  line-height: 20px;
}
</style>