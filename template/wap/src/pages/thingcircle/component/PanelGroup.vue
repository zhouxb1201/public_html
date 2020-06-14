<template>
  <div class="wrap">
    <div class="item-modbox" v-for="(item,index) in items" :key="index">
      <div class="avatar">
        <img :src="item.user_headimg" :onerror="$ERRORPIC.noAvatar" />
      </div>
      <div class="comments">
        <div @click="toDetail(item.id,item.thing_type)">
          <h3 class="name">{{item.thing_user_name}}</h3>
          <div class="title">
            <span v-if="item.topic_title">#{{item.topic_title}}#</span>
            {{item.title}}
          </div>
          <div class="content" v-if="item.content">
            <p>{{item.content}}</p>
            <!--<span>全文</span>-->
          </div>
          <div class="img-group" v-if="item.thing_type == 1">
            <div class="item" v-for="(img,tindex) in item.img_temp_array" :key="tindex">
              <div class="box e-handle">
                <img :src="img.pic_cover" :onerror="$ERRORPIC.noGoods" />
              </div>
            </div>
          </div>
          <div class="img-group" v-else>
            <div class="item">
               <div class="box e-handle">
                <img :src="item.video_img.pic_cover" :onerror="$ERRORPIC.noGoods" />
              </div> 
            </div>
          </div>
          <div class="location" v-if="item.location">{{item.location}}</div>
        </div>
        <div class="btm">
          <div class="time">{{item.create_time | getTimer}}</div>
          <div class="right">
            <div class="fabulous" @click="onFabulous(item)">
              <van-icon
                :name="item.is_like ? 'like' : 'like-o'"
                :class="item.is_like ? color.yes : color.no"
                size="14px"
              />
              <span>{{item.likes | praise}}</span>
            </div>
            <div class="fabulous" @click="toDetail(item.id,item.thing_type)">
              <van-icon name="comment-o" size="14px" />
              <span>{{item.comment.data.length | praise}}</span>
            </div>
          </div>
        </div>
        <!--评论-->
        <div
          class="discuss"
          v-if="item.comment.total_count > 0"
          @click="toDetail(item.id,item.thing_type)"
        >
          <div class="box">
            <div class="text" v-for="(child,cindex) in item.comment.data" :key="cindex">
              <span>{{child.thing_user_name}}：</span>
              {{child.content}}
            </div>
          </div>
          <!--<p class="all">查看所有评论</p>-->
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
  },
  components: {}
};
</script>

<style scoped>
.wrap {
  position: relative;
  background-color: #fff;
  margin-top: 10px;
}
.item-modbox {
  display: flex;
  position: relative;
  padding: 10px 0px;
}
.item-modbox::before {
  content: "";
  position: absolute;
  pointer-events: none;
  box-sizing: border-box;
  left: 0;
  top: 0;
  transform: scaleY(0.5);
  border-top: 1px solid #ebedf0;
  width: 100%;
}
.item-modbox .avatar {
  width: 36px;
  height: 36px;
  margin: 0px 15px;
}
.item-modbox .avatar img {
  display: block;
  width: 100%;
  height: 100%;
  border-radius: 50%;
}
.item-modbox .comments {
  flex: 1;
  margin-right: 15px;
}
.item-modbox .comments .name {
  font-weight: 700;
  line-height: 20px;
  font-size: 14px;
  padding-bottom: 2px;
  color: #6790db;
}
.item-modbox .comments .title {
  font-size: 12px;
  line-height: 16px;
  word-break: break-all;
}
.item-modbox .comments .title span {
  color: #6790db;
}
.item-modbox .comments .content {
  font-size: 12px;
  line-height: 18px;
  padding-top: 8px;
  word-break: break-all;
}
.item-modbox .comments .content p {
  max-height: 94px;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-box-orient: vertical;
  -webkit-line-clamp: 5;
}
.item-modbox .comments .content span {
  display: block;
  line-height: 30px;
  color: #6790db;
}
.item-modbox .comments .img-group {
  margin: 0 -4px;
  overflow: hidden;
}

.item-modbox .comments .img-group .item {
  position: relative;
  width: calc(25% - 8px);
  float: left;
  margin: 4px;
}

.item-modbox .comments .img-group .box {
  width: 100%;
  position: relative;
  padding-bottom: 100%;
  overflow: hidden;
  background: #f9f9f9;
  border-radius: 4px;
}

.item-modbox .comments .img-group img {
  display: block;
  width: 100%;
  background-color: #fff;
  border: none;
  position: absolute;
  border-radius: 4px;
}
.item-modbox .comments .location {
  font-size: 12px;
  color: #6790db;
  margin-top: 10px;
}
.item-modbox .comments .btm {
  display: flex;
  margin-top: 10px;
}
.item-modbox .comments .btm .time {
  font-size: 10px;
  color: #999;
  flex: 1;
}
.item-modbox .comments .btm .right {
  display: flex;
  flex-direction: row;
  overflow: hidden;
  flex: 0.4;
}
.fabulous {
  width: 50%;
  display: flex;
  align-items: flex-end;
  color: #999;
  margin-left: 10px;
}
.fabulous span {
  display: inline-block;
  text-overflow: ellipsis;
  overflow: hidden;
  white-space: nowrap;
  font-size: 10px;
  padding-left: 2px;
}
.discuss {
  background-color: #f6f5f5;
  padding: 10px;
  margin-top: 10px;
  font-size: 12px;
  overflow: hidden;
}
.discuss .text {
  line-height: 20px;
  word-break: break-all;
}
.discuss span {
  color: #6790db;
}
.discuss .all {
  color: #6790db;
  padding-top: 10px;
}
.cr-ff454e {
  color: #ff454e;
}
.cr-999 {
  color: #999;
}
</style>