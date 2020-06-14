<template>
  <div class="waterfall">
    <div class="item" v-for="(item,index) in items" :key="index">
      <div @click="toDetail(item.id,item.thing_type)">
        <img
          :src="item.img_temp_array.length > 0 ? item.img_temp_array[0].pic_cover : ''"
          :onerror="$ERRORPIC.noGoods"
          class="img"
          v-if="item.thing_type == 1"
        />
        <img
          :src="item.video_img.pic_cover"
          :onerror="$ERRORPIC.noGoods"
          class="img"
          v-else
        />
        <p class="title">{{item.title}}</p>
      </div>
      <div class="bottom">
        <div class="user-info">
          <img :src="item.user_headimg" :onerror="$ERRORPIC.noAvatar" class="portrait" />
          <span class="name">{{item.thing_user_name}}</span>
        </div>
        <div class="fabulous" @click="onFabulous(item)">
          <van-icon
            :name="item.is_like ? 'like' : 'like-o'"
            :class="item.is_like ? color.yes : color.no"
            size="12px"
          />
          <span>{{item.likes | praise}}</span>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { GET_THINGCIRCLELIKES } from "@/api/thingcircle";
export default {
  data() {
    return {
      color: {
        yes: "cr-ff454e",
        no: "cr-999"
      }
    };
  },
  props: {
    items: [Object, Array]
  },
  mounted() {},
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
    },
    onFabulous(data) {
      const $this = this;
      let params = {
        thing_id: data.id
      };
      GET_THINGCIRCLELIKES(params).then(res => {
        if (res.code == 1) {
          if (res.message == "取消点赞成功") {
            data.is_like = 0;
          } else {
            data.is_like = 1;
          }
          data.likes = res.count;
        }
      });
    }
  }
};
</script>

<style scoped>
.waterfall {
  padding: 10px;
  column-gap: 10px;
  column-count: 2;
  margin: 0 auto;
}
.waterfall .item {
  margin-bottom: 10px;
  break-inside: avoid;
  border-radius: 10px;
  background-color: #ffffff;
}
.waterfall .item:last-child {
  margin-bottom: 0px;
}
.waterfall .img {
  width: 100%;
  display: block;
  border-top-left-radius: 10px;
  border-top-right-radius: 10px;
}
.waterfall .title {
  font-size: 14px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 2;
  padding: 0px 6px;
  margin: 6px 0px;
  line-height: 18px;
}
.waterfall .bottom {
  padding-bottom: 6px;
  padding-left: 6px;
  padding-right: 6px;
  display: flex;
}
.user-info {
  width: 60%;
  display: flex;
  align-items: center;
}
.user-info .portrait {
  display: inline-block;
  width: 18px;
  height: 18px;
  border-radius: 50%;
}
.user-info .name {
  display: inline-block;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
  font-size: 12px;
  padding-left: 2px;
}
.fabulous {
  width: 40%;
  display: flex;
  align-items: center;
  justify-content: flex-end;
}
.fabulous span {
  display: inline-block;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
  font-size: 10px;
  padding-left: 2px;
}
.cr-ff454e {
  color: #ff454e;
}
.cr-999 {
  color: #999;
}
</style>